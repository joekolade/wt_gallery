<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Alexander Kellner <alexander.kellner@einpraegsam.net>
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
require_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/class.tx_wtgallery_div.php'); // load div class


/**
 * Plugin 'tx_wtgallery_pi1' for the 'wt_gallery' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	wt_gallery
 * @function This Class offeres different pagebrowser methods
 */
class user_wtgallery_pagebrowser extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1'; // Plugin name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_list.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.


	/**
	 * Function pagemenu() offers a menu for category- or listview in wt_gallery
	 * Example output: Page1 | Page2 | Page3
	 *
	 * @param	string		$content: Empty
	 * @param	array		$conf: The PlugIn Configuration
	 * @return	Menuarray with links and titles
	 */
	function pagemenu($content = '', $conf = array()) {
		// config
		global $TSFE;
		$cObj = $TSFE->cObj; // cObject
		$this->conf = $conf; // conf
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$menuarray = array(); // init array for menu
		$conf['userFunc.']['pointer'] = 0; // start pointer with 0 (will be increased in every loop)
		$mode = (!empty($this->conf['userFunc.']['mode']) ? $this->conf['userFunc.']['mode'] : 'list'); // set mode (could be "category" or "list")
		
		// let's go
		for ($i=0; $i < ceil($conf['userFunc.']['overall'] / $conf['userFunc.']['perPage']); $i++) { // one loop for every page
			if ($conf['userFunc.']['pointer'] == intval($_GET[$this->prefixId][$mode . 'pointer'])) $menuarray[$i]['ITEM_STATE'] = 'ACT'; // act status for menu
			
			$menuarray[$i]['title'] = sprintf($this->pi_getLL('wtgallery_ll_pagebrowser_page', 'page '.($i+1)), ($i+1)); // menu label
			$menuarray[$i]['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array($mode . 'pointer' => $conf['userFunc.']['pointer']), 1); // url for menu
			$conf['userFunc.']['pointer'] = ($i+1);
		};
		
		return $menuarray;
	}


	/**
	 * Function clickmenu() offers a menu for category- or listview in wt_gallery
	 * Example output: Previous / Next
	 *
	 * @param	string		$content: Empty
	 * @param	array		$conf: The PlugIn Configuration
	 * @return	Menuarray with links and titles
	 */
	function clickmenu($content = '', $conf = array()) {
		// config
		global $TSFE;
		$cObj = $TSFE->cObj; // cObject
		$this->conf = $conf; // conf
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$menuarray = $pic = array(); $force = 0;
		$mode = (!empty($this->conf['userFunc.']['mode']) ? $this->conf['userFunc.']['mode'] : 'list'); // set mode
		$no = array(
			'next' => (intval($_GET[$this->prefixId][$mode . 'pointer']) + 1), // increase current pointer with one
			'previous' => (intval($_GET[$this->prefixId][$mode . 'pointer']) - 1) // decrease current pointer with one
		);
		
		// Get picture array (only if category.forceFolder is set and only in category mode)
		if ($mode == 'category' && $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.']['category.']['forceFolder'] != '') { // if forceFolder is set
			$force = 1; // activate forceFolder
			for ($i=0; $i<count($this->conf['userFunc.']['folders']); $i++) { // one loop for every subfolder
				$tmp_pic = $this->div->getFiles($GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.'], $this->conf['userFunc.']['startpath'] . $this->conf['userFunc.']['folders'][$i] . '/', $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.']['category.']['previewpicture_order'], 1, 0); // get picture of current subfolder
				$pic[] = $this->div->fileInfo($tmp_pic[0], 'dirname', 1); // get hash from current picture
			}
		}
		
		// previous link
		if (intval($_GET[$this->prefixId][$mode . 'pointer']) != 0) { // show prev link only if this is not the first cat
			if (!$force) { // forceFolder not active?
				$menuarray[0]['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array($mode . 'pointer' => $no['previous']), 1); // url for menu
			} else { // forceFolder active
				$tmp_params = array(
					'parameter' => $GLOBALS['TSFE']->id, 
					'additionalParams' => '&' . $this->prefixId . '[' . $mode . 'pointer]=' . $no['previous'] . '&' . ($force ? $this->prefixId . '[category]=' . $pic[$no['previous']] : ''), 
					'useCacheHash' => 1, 
					'returnLast' => 'url'
				);
				$menuarray[0]['_OVERRIDE_HREF'] = $cObj->typolink('x', $tmp_params); // url for menu
			}
			if (!is_array($this->conf['userFunc.']['clickmenu.']['previous']))
				$menuarray[0]['title'] = $this->cObj->cObjGetSingle($this->conf['userFunc.']['clickmenu.']['previous'].' <b>xXx5</b> ', $this->conf['userFunc.']['clickmenu.']['previous.']); // menu label
		}
		
		// next link
		if (intval($_GET[$this->prefixId][$mode . 'pointer']) < (ceil($conf['userFunc.']['overall'] / $conf['userFunc.']['perPage']) - 1)) { // if current pointer is smaller than all categories
			if (!$force) { // forceFolder not active?
				$menuarray[1]['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array($mode . 'pointer' => $no['next']), 1); // url for menu
			} else { // forceFolder active
				$tmp_params = array(
					'parameter' => $GLOBALS['TSFE']->id, 
					'additionalParams' => '&' . $this->prefixId . '[' . $mode . 'pointer]=' . $no['next'] . '&' . ($force ? $this->prefixId . '[category]=' . $pic[$no['next']] : ''), 
					'useCacheHash' => 1, 
					'returnLast' => 'url'
				);
				$menuarray[1]['_OVERRIDE_HREF'] = $cObj->typolink('x', $tmp_params); // url for menu
			}
			if (!is_array($this->conf['userFunc.']['clickmenu.']['next']))
				$menuarray[1]['title'] = $this->cObj->cObjGetSingle($this->conf['userFunc.']['clickmenu.']['next'], $this->conf['userFunc.']['clickmenu.']['next.']); // menu label
		}
		
		return $menuarray;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/user_wtgallery_pagebrowser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/user_wtgallery_pagebrowser.php']);
}

?>