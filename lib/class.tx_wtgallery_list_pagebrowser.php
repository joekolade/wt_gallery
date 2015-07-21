<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alexander Kellner <alexander.kellner@einpraegsam.net>
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


class tx_wtgallery_list_pagebrowser extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_list.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	
	function start($conf, $piVars, $cObj, $pbarray) {
		// Config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pbarray = $pbarray;
		$this->markerArray = array();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->tmpl = array ('pagebrowser' => $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['list']), '###WTGALLERY_LIST_PAGEBROWSER###')); // Load HTML Template for pagebrowser

		// let's go
		$this->markerArray['###CURRENT_MIN###'] = ($this->pbarray['pointer'] * ($this->conf['list.']['rows'] * $this->conf['list.']['columns'])) + 1; // Current page: From
		$this->markerArray['###CURRENT_MAX###'] = ($this->pbarray['pointer'] * ($this->conf['list.']['rows'] * $this->conf['list.']['columns'])) + $this->pbarray['overall_cur']; // Current page: up to
		if ($this->markerArray['###CURRENT_MAX###'] > $this->pbarray['overall']) $this->markerArray['###CURRENT_MAX###'] = $this->pbarray['overall']; // set maximum
		$this->markerArray['###OVERALL###'] = $this->pbarray['overall']; // Overall addresses
		$this->conf['list.']['pagebrowser.']['special.']['userFunc.'] = array_merge((array) $this->conf['list.']['pagebrowser.']['special.']['userFunc.'], (array) $this->pbarray); // config for pagebrowser userfunc
		$this->conf['list.']['pagebrowser.']['special.']['userFunc.']['mode'] = 'list'; // transmit the mode
		if ((($this->conf['list.']['rows'] * $this->conf['list.']['columns']) < $this->pbarray['overall']) && !is_array($this->conf['list.']['pagebrowser']))
			$this->markerArray['###PAGELINKS###'] = $this->cObj->cObjGetSingle($this->conf['list.']['pagebrowser'], $this->conf['list.']['pagebrowser.']); // Pagebrowser menu (show only if needed)
		
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['pagebrowser'], $this->markerArray); // substitute Marker in Template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		if (!empty($this->content) && $this->pbarray['overall'] > 0) return $this->content; // return only if results
    }	
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_list_pagebrowser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_list_pagebrowser.php']);
}

?>