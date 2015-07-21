<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Alex Kellner <alexander.kellner@einpraegsam.net>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

//require_once(PATH_tslib.'class.tslib_pibase.php');
if (!class_exists('tslib_pibase')) require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('wt_gallery') . 'pi1/class.tx_wtgallery_single.php'); // load class for single view
require_once(t3lib_extMgm::extPath('wt_gallery') . 'pi1/class.tx_wtgallery_list.php'); // load class for list view
require_once(t3lib_extMgm::extPath('wt_gallery') . 'pi1/class.tx_wtgallery_category.php'); // load class for category view
require_once(t3lib_extMgm::extPath('wt_gallery') . 'pi1/class.tx_wtgallery_cooliris.php'); // load class for cooliris view
require_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/class.tx_wtgallery_div.php'); // load div class
require_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/class.tx_wtgallery_coolirisrss.php'); // load class for cooliris RSS
if (t3lib_extMgm::isLoaded('wt_doorman', 0)) require_once(t3lib_extMgm::extPath('wt_doorman') . 'class.tx_wtdoorman_security.php'); // load security class

/**
 * Plugin 'WT Gallery' for the 'wt_gallery' extension.
 *
 * @author	Alex <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	tx_wtgallery
 */
class tx_wtgallery_pi1 extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'wt_gallery';	// The extension key.
	
	
	// Gallery main function
	function main ($content, $conf)	{
		$this->content = $content;
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->single = t3lib_div::makeInstance('tx_wtgallery_single'); // Create new instance for single class
		$this->list = t3lib_div::makeInstance('tx_wtgallery_list'); // Create new instance for single class
		$this->category = t3lib_div::makeInstance('tx_wtgallery_category'); // Create new instance for category class
		$this->cooliris = t3lib_div::makeInstance('tx_wtgallery_cooliris'); // Create new instance for cooliris view
		$this->coolirisRSS = t3lib_div::makeInstance('tx_wtgallery_coolirisrss'); // Create new instance for cooliris RSS
		
		// config
		$this->secure(); // Clean piVars
		$this->config(); // Enable flexform values in config
		$this->check(); // Fast check if all is ok
		$mode = t3lib_div::trimExplode(',', $this->conf['main.']['mode'], 1); // split mode
		
		// let's go
		if ($this->conf['main.']['mode'] && $this->conf['main.']['path']) { // only if mode and path are set
			for ($i=0; $i < count($mode); $i++) { // One loop for every selected mode
				
				switch ($mode[$i]) { // mode
					case 'single': // if single mode is selected
						$this->content .= $this->single->start($this->conf, $this->piVars, $this->cObj); // get single view
						break;
						
					case 'list': // if list mode is selected
						$this->content .= $this->list->start($this->conf, $this->piVars, $this->cObj); // get list view
						break;
						
					case 'category': // if category mode is selected
						$this->content .= $this->category->start($this->conf, $this->piVars, $this->cObj); // get category view
						break;
						
					case 'cooliris': // if cooliris mode is selected
						$this->content .= $this->cooliris->start($this->conf, $this->piVars, $this->cObj); // get cooliris listview
						break;
				}
				
			}
		}
		
		if (t3lib_div::_GP('type') == 3135) { // typenum is 3135 (rss feed for cooliris)
			return $this->coolirisRSS->start($this->conf, $this->piVars, $this->cObj); // RSS Feed
		}
		
		return $this->pi_wrapInBaseClass($this->content);
	}
	
	
	// enables flexform values in $conf
	function config() {
		// 1. add flexform values to $this->conf
		if (is_array($this->cObj->data['pi_flexform']['data'])) { // if there are flexform values
			foreach ($this->cObj->data['pi_flexform']['data'] as $key => $value) { // every flexform category
				if (count($this->cObj->data['pi_flexform']['data'][$key]['lDEF']) > 0) { // if there are flexform values
					foreach ($this->cObj->data['pi_flexform']['data'][$key]['lDEF'] as $key2 => $value2) { // every flexform option
						if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key2, $key)) { // if value exists in flexform
							$this->conf[$key . '.'][$key2] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key2, $key); // overwrite $this->conf
						}
					}
				}
			}
		}
		
		// 2. validate picture path
		if ($this->conf['main.']['path']) { // if picture path exists 
			if (substr($this->conf['main.']['path'], -1, 1) != '/') $this->conf['main.']['path'] .= '/'; // add slash at the end if this is missing
			if (substr($this->conf['main.']['path'], 0, 1) == '/') $this->conf['main.']['path'] = substr($this->conf['main.']['path'], 1); // remove first slash if exits
			if (!t3lib_div::validPathStr($this->conf['main.']['path'])) { // picture path is not valid
				die ($this->extKey . ': Picture path not valid - please correct it!'); // stop script
			}
		}
		
		// 3. set value for columns and rows if no value is set
		if (empty($this->conf['list.']['rows'])) $this->conf['list.']['rows'] = 1000; // set 1000 for default lines
		if (empty($this->conf['list.']['columns'])) $this->conf['list.']['columns'] = 1; // set 1 for default columns
		if (empty($this->conf['category.']['rows'])) $this->conf['category.']['rows'] = 1000; // set 1000 for default lines
		if (empty($this->conf['category.']['columns'])) $this->conf['category.']['columns'] = 1; // set 1 for default columns
		
		// 4. add hook for conf manipulation
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['setup'])) { // Adds hook for processing
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['setup'] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->setup($this->conf, $this->piVars, $this); // Enable setup manipulation
			}
		}
		
		// 5. set pid single and pid list to current page if not set
		if (intval($this->conf['single.']['pid_single']) == 0) $this->conf['single.']['pid_single'] = $GLOBALS['TSFE']->id; // set single pid to current pid if not set
		if (intval($this->conf['list.']['pid_list']) == 0) $this->conf['list.']['pid_list'] = $GLOBALS['TSFE']->id; // set list pid to current pid if not set
	}
	
	
	// Function secure() uses wt_doorman to clear piVars
	function secure() {
		if (class_exists('tx_wtdoorman_security')) { // if doorman class exits
			$this->sec = t3lib_div::makeInstance('tx_wtdoorman_security'); // Create new instance for security class
			$this->sec->secParams = array ( // Allowed piVars type (int, text, alphanum, "value")
				'show' => 'int', // should be integer
				//'category' => 'alphanum ++ \/\._@-', // alphanum for folders extended with '/', '.' and '_'
				'category' => 'int', // category has to be an integer
				'listpointer' => 'int', // pointer for pagebrowser in listview should be integer
				'categorypointer' => 'int' // pointer for pagebrowser in categoryview should be integer
			);
			$this->piVars = $this->sec->sec($this->piVars); // overwrite piVars with piVars from doorman class
		} else die ($this->extKey . ': Extension wt_doorman not found, please install first!'); // stop script
	}
	
	
	// Function check() makes a fast check if all is ok
	function check() {
		$this->div->init($this->conf); // init function
		if (t3lib_div::_GP('type') != 3135) {
			$this->content .= $this->div->check4errors($this->conf['main.']['path'], 'Picture path not set - set in flexform or in constants'); // check for picture path
			$this->content .= $this->div->check4errors($this->conf['main.']['mode'], 'Mode not set - set mode in flexform or in constants'); // check for mode
			$this->content .= $this->div->check4errors($this->piVars['category'], 'No valid picture path', 2, 1); // check for correct path
		}
	}

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_pi1.php']);
}

?>