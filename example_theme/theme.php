<?php
// This file was modified by Jonathan Hall on 2024-03-15

class ExampleTheme extends OsTicketTheme {
	
	function getName() {
		return 'Example Theme';
	}
	
	function getHeaderStyles($isStaffView) {
		return [ $this->getBaseUrl().'style.css' ];
	}
	
	function getHeaderScripts($isStaffView) {
		return [ ];
	}
	
	function getFooterScripts($isStaffView) {
		return [ ];
	}
	
	function getMinimumLogoAspectRatio() {
		return -1;	
	}
	
	function getThemeSettingsFields() {
		return [
			'fontFamily' => new ChoiceField([
				'label' => 'Font Family',
				'hint' => '',
				'choices' => [
					'Arial' => 'Arial',
					'Open Sans' => 'Open Sans',
					'Times New Roman' => 'Times New Roman'
				],
				'default' => 'Arial'
			]),
			'primaryColor' => new ColorChoiceField([
				'label' => 'Primary Color',
				'hint' => '',
				'default' => ''
			]),
			'secondaryColor' => new ColorChoiceField([
				'label' => 'Secondary Color',
				'hint' => '',
				'default' => ''
			]),
			'logoWidth' => new TextboxField([
				'label' => 'Logo Width',
				'hint' => '',
				'default' => ''
			])
		];
	}
	
	
}