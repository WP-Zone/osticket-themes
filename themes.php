<?php
// This file was modified by Jonathan Hall on 2024-03-15

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.thread.php');
require_once(INCLUDE_DIR . 'class.config.php');
require_once(INCLUDE_DIR . 'class.format.php');
require_once(INCLUDE_DIR . 'class.osticket.php');
require_once(__DIR__ . '/includes/class.theme.php');

class WPZThemesPlugin extends Plugin {
	public static $instance;
	private $activeTheme = null;
	public $config_class = 'WPZThemesPluginConfig';

    function bootstrap() {
		self::$instance = $this;
		$activeTheme = $this->getConfig($this->getInstances()->first())->get('wpz-theme');
		if ($activeTheme) {
			try {
				$this->activeTheme = OsTicketTheme::getTheme($activeTheme);
				$this->activeTheme->init();
			} catch (Exception $ex) { }
		}
    }
	
	function isMultiInstance() {
		return false;
	}
	
	function getActiveTheme() {
		return $this->activeTheme;
	}
}

class WPZThemesPluginConfig extends PluginConfig {
	function getOptions() {
		if (!empty(WPZThemesPlugin::$instance)) {
			$activeTheme = WPZThemesPlugin::$instance->getActiveTheme();
			if (!empty($activeTheme)) {
				$themeSettingsFields = $activeTheme->getThemeSettingsFields();
			}
		}
		
		if (!empty($themeSettingsFields)) {
			$_themeSettingsFields = $themeSettingsFields;
			$themeSettingsFields = [];
			$themeSettingPrefix = 'wpz-theme-'.substr(md5($activeTheme->getId()), 0, 9).'-';
			foreach ($_themeSettingsFields as $settingId => $setting) {
				$themeSettingsFields[$themeSettingPrefix.$settingId] = $setting;
			}
		}
		
		
		return array_merge(
			[
				'wpz-theme' => new ChoiceField([
					'label' => 'Theme',
					'hint' => '',
					'choices' => array_merge(['' => 'Default'], OsTicketTheme::getThemes()),
					'default' => ''
				])
			],
			empty($themeSettingsFields) ? [] : [
				'wpz-theme-separator' => new SectionBreakField([
					'label' => $activeTheme->getName().' Settings'
				])
			],
			empty($themeSettingsFields) ? [] : $themeSettingsFields
		);
	}
	
	function store(SimpleForm $form = null, &$errors=array()) {
		if (!empty(WPZThemesPlugin::$instance)) {
			$activeTheme = WPZThemesPlugin::$instance->getActiveTheme();
		}
		
		$result = parent::store($form, $errors);
		
		if ($result && !empty($activeTheme)) {
			try {
				$activeTheme->buildAllDynamicCss();
			} catch (Exception $ex) {
				$result = false;
			}
		}
		
		return $result;
		
	}
}

