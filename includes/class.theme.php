<?php
// This file was modified by Jonathan Hall on 2024-02-22

abstract class OsTicketTheme {
	const THEMES_DIR = INCLUDE_DIR.'themes/';
	protected $themeId;
	
	function __construct($themeId) {
		if (!self::validateThemeId($themeId)) {
			throw new Exception('Invalid theme ID');
		}
		$this->themeId = $themeId;
	}
	
	function init() {
		global $ost;
		
		$extraHead = implode("\n", array_map([$this, 'getStyleHtml'], $this->getHeaderStyles()))
					.implode("\n", array_map([$this, 'getScriptHtml'], $this->getHeaderScripts()));
		if ($extraHead) {
			$ost->addExtraHeader($extraHead);
		}
	}
	
	function getBaseUrl() {
		return osTicket::get_base_url().'include/themes/'.$this->themeId.'/';
	}
	
	protected function getScriptHtml($url) {
		return '<script src="'.htmlspecialchars($url).'"></script>';
	}
	
	protected function getStyleHtml($url) {
		return '<link rel="stylesheet" href="'.htmlspecialchars($url).'">';
	}
	
	
	/** Intended for override by theme subclasses **/
	
	abstract function getName();
	
	function getHeaderScripts() {
		return [];
	}
	
	function getHeaderStyles() {
		return [];
	}
	
	
	/** Static **/
	
	private static function validateThemeId($themeId) {
		return !preg_match('/[^[:alnum:]\\-_]/', $themeId);
	}
	
	static function getThemes() {
		$themes = [];
		foreach (scandir(self::THEMES_DIR) as $dirItem) {
			try {
				$theme = self::getTheme($dirItem);
				$themes[$dirItem] = $theme->getName();
			} catch (Exception $ex) { }
		}
		return $themes;
	}
	
	static function getTheme($themeId) {
		if (!self::validateThemeId($themeId) || !file_exists(self::THEMES_DIR.$dirItem.'/theme.php')) {
			throw new Exception('Invalid theme ID');
		}
		
		$themeClass = include_once(self::THEMES_DIR.$themeId.'/theme.php');
		
		if (!$themeClass || !class_exists($themeClass)) {
			throw new Exception('Invalid theme (missing class)');
		}
		
		$theme = new $themeClass($themeId);
		
		if (!@is_a($theme, 'OsTicketTheme')) {
			throw new Exception('Invalid theme (not a theme object)');
		}
		
		return $theme;
	}
}