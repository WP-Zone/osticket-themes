<?php
// This file was modified by Jonathan Hall on 2024-03-15

abstract class OsTicketTheme {
	const THEMES_DIR = ROOT_DIR.'themes/';
	protected $themeId;
	protected $inCapture;
	protected $inElement;
	protected $openElements = 0;
	protected $currentCapture = '';
	protected $captures = [];
	protected $buffer = [];
	protected $bufferRelease = [];
	
	function __construct($themeId) {
		if (!self::validateThemeId($themeId)) {
			throw new Exception('Invalid theme ID');
		}
		$this->themeId = $themeId;
	}
	
	function init() {
		global $ost;
		
		if (!empty($_FILES)) {
			$_FILES = new ArrayEventListener($_FILES, [$this, 'handleUploadFilesAccess']);
		}
		
		ob_start([$this, 'processOutput']);
	}
	
	function getMinimumLogoAspectRatio() {
		return 2;	
	}
	
	function getId() {
		return $this->themeId;
	}
	
	function getThemeSettingsFields() {
		return [];
	}
	
	function handleUploadFilesAccess($accessEventType, $fileId) {
		if ($fileId == 'logo') {
			switch ($accessEventType) {
				case ArrayEventListener::EVENT_CHECK:
				case ArrayEventListener::EVENT_READ:
					$_FILES = $_FILES->unwrap();
					try {
						$this->handleLogoUpload($_FILES['logo']);
						unset($_FILES['logo']);
						return null;
					} catch (Exception $ex) { }
					break;
			}
		}
		return true;
	}
	
	private function handleLogoUpload($fileData) {
		if (self::isLoggedInAsAdmin()) {
			$fileData = AttachmentFile::format($fileData);
			if (!empty($fileData[0]) && empty($fileData[0]['error'])) {
				$error = '';
				if (AttachmentFile::uploadLogo($fileData[0], $error, $this->getMinimumLogoAspectRatio())) {
					return true;
				}
			}
		}
		throw new Exception();
	}
	
	function processOutput($output) {
		global $thisstaff;
		
		$headPos = stripos($output, '</head>');
		if ($headPos !== false) {
			$extraHead = implode("\n", array_map([$this, 'getStyleHtml'], $this->getHeaderStyles(!empty($thisstaff))))
					.implode("\n", array_map([$this, 'getScriptHtml'], $this->getHeaderScripts(!empty($thisstaff))));
			$output = substr($output, 0, $headPos).$extraHead.substr($output, $headPos);
		}
		
		$bodyPos = stripos($output, '</body>');
		if ($bodyPos !== false) {
			$extraFoot = implode("\n", array_map([$this, 'getScriptHtml'], $this->getFooterScripts(!empty($thisstaff))));
			$output = substr($output, 0, $bodyPos).$extraFoot.substr($output, $bodyPos);
		}
		
		if ($thisstaff) {
			$capture = [
				'topnav' => ' id="info"',
				'logo' => ' id="logo"',
				'nav' => ' id="nav"',
				'subnav' => '<nav ',
				'footer' => ' id="footer"'
			];
			$templates = [
				'header' => [
					'contains' => ['topnav', 'logo'],
					'holdUntil' => ['nav', 'footer']
				],
				'nav' => [
					'contains' => ['nav', 'subnav']
				],
				'footer' => [
					'contains' => ['footer'],
					'wrapInLast' => true
				]
			];
			$output = $this->doCapture($output, $capture, $templates);
		} else {
			$capture = [
				'topnav' => ' class="pull-right flush-right"',
				'logo' => ' id="logo"',
				'nav' => ' id="nav"',
				'landing' => ' id="landing_page"',
				'content' => ' id="content"',
				'footer' => ' id="footer"',
			];
			$templates = [
				'header' => [
					'contains' => ['topnav', 'logo'],
					'holdUntil' => ['nav', 'footer']
				],
				'nav' => [
					'contains' => ['nav']
				],
				'footer' => [
					'contains' => ['footer'],
					'wrapInLast' => true
				],
				'landing_page' => [
					'contains' => ['landing'],
					'wrapInLast' => true
				],
				'login_page' => [
					'contains' => ['content'],
					'onlyOn' => ['login.php']
				],
				'tickets' => [
					'contains' => ['content'],
					'onlyOn' => [ 'tickets.php']
				],
				'view' => [
					'contains' => ['content'],
					'onlyOn' => ['view.php']
				],
			];
			$output = $this->doCapture($output, $capture, $templates);
		}
			
			
			
		return $output;
	}
	
	static function isLoggedInAsClient() {
		global $thisclient;
		
		// based on client.inc.php
		return !empty($thisclient) && $thisclient->getId() && $thisclient->isValid();
	}
	
	static function isLoggedInAsStaff() {
		global $thisstaff;
		
		// based on client.inc.php
		return !empty($thisstaff) && $thisstaff->getId() && $thisstaff->isValid();
	}
	
	static function isLoggedInAsAdmin() {
		global $thisstaff;
		return self::isLoggedInAsStaff() && $thisstaff->isAdmin();
	}
	
	function doCapture($output, $capture, $templates) {
		global $thisstaff;
		if ($this->inCapture) {
			$outputTags = explode('<', $output);
			foreach ($outputTags as $i => $outputTag) {
				if (in_array(substr($outputTag, 0, strlen($this->inElement) + 1), [$this->inElement.' ', $this->inElement.'>',], true)) {
					++$this->openElements;
				} else if (substr($outputTag, 0, strlen($this->inElement) + 2) == '/'.$this->inElement.'>') {
					if ($this->openElements) {
						--$this->openElements;	
					} else {
						$outputTag = substr($outputTag, strlen($this->inElement) + 2);
						foreach ($templates as $templateId => $templateData) {
							if (in_array($this->inCapture, $templateData['contains']) && (empty($templateData['onlyOn']) || in_array($this->getCurrentPage(), $templateData['onlyOn'], true))) {
								$template = $templateId;
								break;
							}
						}
						
						$this->currentCapture .= '</'.$this->inElement.'>';
						if (method_exists($this, 'processCapture_'.$this->inCapture)) {
							$this->currentCapture = call_user_func([$this, 'processCapture_'.$this->inCapture], $this->currentCapture);
						}
						$this->captures[ $this->inCapture ] = '<!-- '.$this->inCapture.' -->'.$this->currentCapture.'<!-- /'.$this->inCapture.' -->';

						if ($template && file_exists(self::THEMES_DIR.$this->themeId.'/templates/'.($thisstaff ? 'staff' : 'clients').'/'.$template.'.php')) {
							if (end($templates[$template]['contains']) == $this->inCapture) {
								$captureReplacementStart = '';
								$captureReplacementEnd = '';
								if (!empty($templates[$template]['wrapInLast'])) {
									$firstTagClosePos = strpos($this->currentCapture, '>');
									$lastTagOpenPos = strrpos($this->currentCapture, '<');
									if ($firstTagClosePos && $lastTagOpenPos) {
										$captureReplacementStart = substr($this->currentCapture, 0, $firstTagClosePos + 1);
										$captureReplacementEnd = substr($this->currentCapture, $lastTagOpenPos);
										$this->captures[ $this->inCapture ] = substr($this->currentCapture, $firstTagClosePos + 1, $lastTagOpenPos - $firstTagClosePos - 1);
									}
								}
								
								if (!empty($templates[$template]['holdUntil'])) {
									$this->bufferRelease = $templates[$template]['holdUntil'];
								}
								
								if ($this->bufferRelease) {
									$captureReplacement = '';
									$this->buffer[] = $captureReplacementStart;
									$this->buffer[] = new FutureTemplate(self::THEMES_DIR.$this->themeId.'/templates/'.($thisstaff ? 'staff' : 'clients').'/'.$template.'.php');
									$this->buffer[] = $captureReplacementEnd;
								} else {
									$captureReplacement = $captureReplacementStart.$this->getTemplate(self::THEMES_DIR.$this->themeId.'/templates/'.($thisstaff ? 'staff' : 'clients').'/'.$template.'.php').$captureReplacementEnd;
								}
							} else {
								$captureReplacement = '';
							}
						} else {
							if ($this->bufferRelease) {
								$captureReplacement = '';
								$this->buffer[] = $this->currentCapture;
							} else {
								$captureReplacement = $this->currentCapture;
							}
						}
						
						$buffer = '';
						if (in_array($template, $this->bufferRelease, true)) {
							foreach ($this->buffer as $bufferItem) {
								$buffer .= is_a($bufferItem, 'FutureTemplate') ? $this->getTemplate($bufferItem->templatePath) : $bufferItem;
							}
							$this->bufferRelease = [];
							$this->buffer = [];
						}


						$this->inElement = null;

						$this->currentCapture = '';
						$this->inCapture = null;

						return $buffer
							.$captureReplacement
							.$this->doCapture(
							$outputTag.(count($outputTags) > $i + 1 ? '<'.implode('<', array_slice($outputTags, $i + 1)) : ''),
							$capture,
							$templates
						);
					}
				}
				
				$this->currentCapture .= ($i ? '<' : '').$outputTag;
			}

		} else {
			foreach (array_diff_key($capture, $this->captures) as $captureKey => $captureMatch) {
				$capturePos = strpos($output, $captureMatch);
				if ($capturePos !== false) {
					$tagStart = strrpos($output, '<', $capturePos - strlen($output));
					if ($tagStart !== false) {
						$tagNameEnd = strpos($output, ' ', $tagStart);
						$tagEnd = strpos($output, '>', $tagNameEnd) + 1;
						$this->inElement = substr($output, $tagStart + 1, $tagNameEnd - $tagStart - 1);
						$this->inCapture = $captureKey;
						
						$this->currentCapture .= substr($output, $tagStart, $tagEnd - $tagStart);
						
						if ($this->bufferRelease) {
							$this->buffer[] = substr($output, 0, $tagStart);
						}
						return ($this->bufferRelease ? '' : substr($output, 0, $tagStart)).$this->doCapture( substr($output, $tagEnd), $capture, $templates );
					}
				}
			}
		}
		return $output;
	}
	
	function processCapture_content($capture) {
		switch ($this->getCurrentPage()) {
			case 'view.php':
				$formStart = stripos($capture, '<form ');
				if ($formStart !== false) {
					$formEnd = stripos($capture, '</form>', $formStart);
					if ($formEnd !== false) {
						$formEnd += 7;
						$ticketStatusForm = substr($capture, $formStart, $formEnd - $formStart);

						preg_match_all('/<(form|input|button)[^>]*>|<label[^>]*>.*?<\/label>/is', $ticketStatusForm, $formTags);
						$ticketStatusForm = '';
						for ($i = 0; $i < count($formTags[0]); ++$i) {
							$ticketStatusForm .= $i ? '<div>'.$formTags[0][$i].'</div>' : $formTags[0][$i];
						}
						$ticketStatusForm .= '</form>';

						$this->captures['ticketstatusform'] = $ticketStatusForm;
					}
				}
				break;
			case 'login.php':
				$this->captures['loginerror'] = '';
				$errorElementIdPos = stripos($capture, ' id="msg_error"');
				if ($errorElementIdPos) {
					$errorElementStartPos = strrpos($capture, '<', $errorElementIdPos - strlen($capture));
					if ($errorElementStartPos !== false) {
						$errorElementTag = strstr( substr($capture, $errorElementStartPos + 1), ' ', true );
						if ($errorElementTag) {
							$errorElementEndPos = stripos($capture, '</'.$errorElementTag.'>', $errorElementStartPos) + strlen($errorElementTag) + 3;
							if ($errorElementEndPos) {
								$this->captures['loginerror'] = substr($capture, $errorElementStartPos, $errorElementEndPos - $errorElementStartPos);
							}
						}
					}
				}
				// no break
			case 'tickets.php':
				$formStart = stripos($capture, '<form ');
				if ($formStart !== false) {
					$formEnd = stripos($capture, '</form>', $formStart);
					if ($formEnd !== false) {
						$formEnd += 7;
					    $loginForm = substr($capture, $formStart, $formEnd - $formStart);

						preg_match_all('/<(form|input|button)[^>]*>|<label[^>]*>.*?<\/label>/is', $loginForm, $loginFormTags);
						$loginForm = '';
						for ($i = 0; $i < count($loginFormTags[0]); ++$i) {
							$loginForm .= $i ? '<div>'.$loginFormTags[0][$i].'</div>' : $loginFormTags[0][$i];
						}
						$loginForm .= '</form>';

						$this->captures['loginform'] = $loginForm;
					}
				}
				break;
		}
		
		return $capture;
	}
	
	protected function getTemplate($path) {
		extract($this->captures);
		return include $path;
	}
	
	function getCurrentPage() {
		return basename($_SERVER['SCRIPT_FILENAME']);
	}
	
	function getBaseUrl() {
		return osTicket::get_base_url().'themes/'.$this->themeId.'/';
	}
	
	function getBasePath() {
		return self::THEMES_DIR.$this->themeId.'/';
	}
	
	protected function getScriptHtml($script) {
		if (is_string($script)) {
			$url = $script;
		} else if (isset($script['path'])) {
			$url = $this->getBaseUrl().$script['path'];
		} else if (isset($script['url'])) {
			$url = $script['url'];
		} else {
			return '';
		}
		return '<script src="'.htmlspecialchars($url).'"></script>';
	}
	
	protected function getStyleHtml($style) {
		if (is_string($style)) {
			$url = $style;
		} else if (isset($style['path'])) {
			$url = $this->getBaseUrl().$style['path'].(empty($style['dynamic']) ? '' : '.build.css');
		} else if (isset($style['url'])) {
			$url = $style['url'];
		} else {
			return '';
		}
		if (!empty($style['dynamic']) && isset($style['path']) && !file_exists($style['path'].'.build.css')) {
			try {
				$this->buildDynamicCss($this->getBasePath().$style['path']);
			} catch (Exception $ex) {
				return;
			}
		}
		return '<link rel="stylesheet" href="'.htmlspecialchars($url).'">';
	}
	
	public function buildAllDynamicCss() {
		array_map(
			[$this, 'buildDynamicCss'],
			array_column(
				array_filter(
					array_merge(
						$this->getHeaderStyles(true),
						$this->getHeaderStyles(false),
					),
					function($style) {
						return is_array($style) && !empty($style['dynamic']) && isset($style['path']);
					}
				),
				'path'
			)
		);
	}
	
	function getThemeSettings() {
		$settings = [];
		if (!empty(WPZThemesPlugin::$instance)) {
			$config = WPZThemesPlugin::$instance->getConfig(WPZThemesPlugin::$instance->getInstances()->first());
			$themeSettingPrefix = 'wpz-theme-'.substr(md5($this->themeId), 0, 9).'-';
			foreach ($this->getThemeSettingsFields() as $settingId => $setting) {
				$settingValue = $config->get($themeSettingPrefix.$settingId);
				if ($settingValue !== null) {
					$settings[$settingId] = $settingValue;
				}
			}
		}
		return $settings;
	}
	
	private function buildDynamicCss($cssPath) {
		$cssPath = $this->getBasePath().$cssPath;
		if (!file_exists($cssPath)) {
			throw new Exception();
		}
		
		$css = file_get_contents($cssPath);
		if ($css === false) {
			throw new Exception();
		}
		
		$themeSettings = $this->getThemeSettings();
		
		$css = preg_replace_callback(
			'#/\\*@\\$([[:alnum:]_-]+)\\*/(.+)/\\*\\$\\1@\\*/#U', 
			function($found) use ($themeSettings) {
				return empty($themeSettings[ $found[1] ])
					? $found[2]
					: (
						(strpos($themeSettings[ $found[1] ], ' ') === false || $themeSettings[ $found[1] ][0] == '"' || $themeSettings[ $found[1] ][0] == '\'')
							? $themeSettings[ $found[1] ]
							: '"'.addslashes($themeSettings[ $found[1] ]).'"'
					);
			},
			$css
		);
		
		$fileHeader = '/*! Modified CSS file generated by the Themes for osTicket plugin at '.gmdate('c').' */'."\n";
		
		file_put_contents($cssPath.'.build.css', $fileHeader.$css);
	}
	
	
	/** Intended for override by theme subclasses **/
	
	abstract function getName();
	
	function getHeaderScripts($isStaffView) {
		return [];
	}
	
	function getFooterScripts($isStaffView) {
		return [];
	}
	
	function getHeaderStyles($isStaffView) {
		return [];
	}
	
	
	/** Static **/
	
	private static function validateThemeId($themeId) {
		return !preg_match('/[^[:alnum:]_]/', $themeId);
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
		if (!self::validateThemeId($themeId) || !file_exists(self::THEMES_DIR.$themeId.'/theme.php')) {
			throw new Exception('Invalid theme ID');
		}
		
		$themeFile = self::THEMES_DIR.$themeId.'/theme.php';
		
		preg_match('/class\\s+([^\\{\\s]+)/i', file_get_contents($themeFile), $themeClassMatch);
		
		include_once($themeFile);
		
		$themeClass = empty($themeClassMatch[1]) ? null : trim($themeClassMatch[1]);
		
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

/** Helper class(es) **/

class FutureTemplate {
	public $templatePath;
	function __construct($templatePath) {
		$this->templatePath = $templatePath;	
	}
}

class ArrayEventListener implements ArrayAccess {
	const EVENT_CHECK = 1;
	const EVENT_READ = 2;
	const EVENT_WRITE = 3;
	const EVENT_UNSET = 4;
	
	private $wrappedArray, $callback;
	
	function __construct($arrayToWrap, $callback) {
		$this->wrappedArray = $arrayToWrap;
		$this->callback = $callback;
	}
	
	function offsetExists($key) {
		return call_user_func($this->callback, self::EVENT_CHECK, $key) !== null && array_key_exists($key, $this->wrappedArray);
	}
	
	function offsetGet($key) {
		if ( call_user_func($this->callback, self::EVENT_READ, $key) !== null ){
			return $this->wrappedArray[$key];
		}
	}
	
	function offsetSet($key, $value) {
		if ( call_user_func($this->callback, self::EVENT_WRITE, $key) !== null ){
			$this->wrappedArray[$key] = $value;
		}
	}
	
	function offsetUnset($key) {
		if ( call_user_func($this->callback, self::EVENT_UNSET, $key) !== null ){
			unset($this->wrappedArray[$key]);
		}
	}
	
	function unwrap() {
		return $this->wrappedArray;	
	}
	
}