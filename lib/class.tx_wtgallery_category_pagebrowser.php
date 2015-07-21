<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alex Kellner <alexander.kellner@einpraegsam.net>
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

require_once(t3lib_extMgm::extPath('wt_gallery').'lib/class.tx_wtgallery_div.php'); // load div class
require_once(t3lib_extMgm::extPath('wt_gallery').'lib/class.tx_wtgallery_dynamicmarkers.php'); // file for dynamicmarker functions

class tx_wtgallery_category_pagebrowser extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_pi1.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	
	function start($conf, $piVars, $cObj, $pbarray) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pbarray = $pbarray;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->tmpl = $this->markerArray = $this->wrappedSubpartArray = array(); // init
		$this->tmpl['category_browser'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['category']), '###WTGALLERY_CATEGORY_PAGEBROWSER###'); // Load HTML Template
		
		// fill markers
		if ($this->pbarray['overall'] != 0) { // if there are some categories
			$this->markerArray['###CURRENT_MIN###'] = ($this->pbarray['pointer'] * ($this->conf['category.']['rows'] * $this->conf['category.']['columns'])) + 1; // Current page: From
			$this->markerArray['###CURRENT_MAX###'] = ($this->pbarray['pointer'] * ($this->conf['category.']['rows'] * $this->conf['category.']['columns'])) + $this->pbarray['overall_cur']; // Current page: up to
			if ($this->markerArray['###CURRENT_MAX###'] > $this->pbarray['overall']) $this->markerArray['###CURRENT_MAX###'] = $this->pbarray['overall']; // set maximum
			$this->markerArray['###OVERALL###'] = $this->pbarray['overall']; // Overall addresses
		}
		if ($this->pbarray['overall'] == 0) $this->markerArray['###WTGALLERY_LL_PAGEBROWSER_UPTO###'] = ''; // clear marker
		if ($this->pbarray['overall'] == 0) $this->markerArray['###WTGALLERY_LL_PAGEBROWSER_WITHIN###'] = ''; // clear marker
		$this->conf['category.']['pagebrowser.']['special.']['userFunc.'] = array_merge((array) $this->conf['category.']['pagebrowser.']['special.']['userFunc.'], (array) $this->pbarray); // config for pagebrowser userfunc
		$this->conf['category.']['pagebrowser.']['special.']['userFunc.']['mode'] = 'category'; // transmit the mode
		if (($this->conf['category.']['rows'] * $this->conf['category.']['columns']) < $this->pbarray['overall'] && !is_array($this->conf['category.']['pagebrowser'])) $this->markerArray['###PAGELINKS###'] = $this->cObj->cObjGetSingle($this->conf['category.']['pagebrowser'], $this->conf['category.']['pagebrowser.']); // Pagebrowser menu (show only if needed)
		
		$row = array ( // write $row for .field in ts
			'startcategory_link' => $this->cObj->typolink('x', array('parameter' => $GLOBALS["TSFE"]->id, 'returnLast' => 'url'))
		);
		if (empty($this->piVars['category'])) $row = array(); // clear if on startcategory
		$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
		if (!is_array($this->conf['category.']['startcategorylink'])) 
			$this->markerArray['###STARTCATEGORYLINK###'] = $this->cObj->cObjGetSingle($this->conf['category.']['startcategorylink'], $this->conf['category.']['startcategorylink.']); // values from ts
		
		// return
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['category_browser'], $this->markerArray, array(), $this->wrappedSubpartArray); // Get html template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		if (!empty($this->content)) return $this->content; // return HTML if $content is not empty
	}	
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_category_pagebrowser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_category_pagebrowser.php']);
}

?>