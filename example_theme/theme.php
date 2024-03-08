<?php
// This file was modified by Jonathan Hall on 2024-03-08

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
	
	
}