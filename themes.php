<?php
// This file was modified by Jonathan Hall on 2024-02-22

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.thread.php');
require_once(INCLUDE_DIR . 'class.config.php');
require_once(INCLUDE_DIR . 'class.format.php');
require_once(INCLUDE_DIR . 'class.osticket.php');
require_once(__DIR__ . '/includes/class.theme.php');

class WPZThemesPlugin extends Plugin {
	private $activeTheme = null;
	public $config_class = 'WPZThemesPluginConfig';

    function bootstrap() {
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
}

class WPZThemesPluginConfig extends PluginConfig {
	function getOptions() {
		return [
			'wpz-theme' => new ChoiceField([
				'label' => 'Theme',
				'hint' => '',
				'choices' => array_merge(['' => 'Default'], OsTicketTheme::getThemes()),
				'default' => ''
			]);
		];
	}
}

