<?php
// This file was modified by Jonathan Hall on 2024-02-22

class ExampleTheme extends OsTicketTheme {
	
	function getName() {
		return 'Example Theme';
	}
	
	function getHeaderStyles() {
		return [ $this->getBaseUrl().'style.css' ];
	}
	
	function getHeaderScripts() {
		return [ ];
	}
	
	
}

return 'ExampleTheme';