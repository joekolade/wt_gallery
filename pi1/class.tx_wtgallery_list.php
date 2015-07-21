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

if (!class_exists('tslib_pibase')) {
	require_once(PATH_tslib . 'class.tslib_pibase.php');
}
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wt_gallery') . 'lib/class.tx_wtgallery_div.php'); // load div class
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wt_gallery') . 'lib/class.tx_wtgallery_dynamicmarkers.php'); // file for dynamicmarker functions
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wt_gallery') . 'lib/class.tx_wtgallery_list_pagebrowser.php'); // file for dynamicmarker functions

class tx_wtgallery_list extends tslib_pibase {
	

	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_list.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	var $mode = 'list'; // kind of mode

	function start($conf, $piVars, $cObj) {


		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->pagebrowser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wtgallery_list_pagebrowser'); // Create new instance for pagebrowser function
		$this->tmpl = $this->markerArray = $this->outerMarkerArray = $subpartArray = array(); $content_item = ''; // init
		$this->tmpl[$this->mode]['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###WTGALLERY_LIST###'); // Load HTML Template
		$this->tmpl[$this->mode]['item'] = $this->cObj->getSubpart($this->tmpl[$this->mode]['all'],'###ITEM###'); // work on subpart 2

		
		// let's go
		$startpath = $this->div->validatePicturePath($this->piVars['category'] ? $this->div->hash2folder($this->piVars['category'], $this->conf['main.']['path']) : $this->conf['main.']['path']); // startpath from piVars or from ts

		if(strpos ( $startpath , ':')){
			
			$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			$f = $storageRepository->findByUid(explode(':', $startpath)[0]);
			
			$startpath = $f->getConfiguration()['basePath'] . explode(':', $startpath)[1];
			
		}

		$pictures = $this->div->getFiles($this->conf, $startpath, $this->conf[$this->mode . '.']['order'], $this->conf[$this->mode . '.']['limit']); // get all pictures from current folder
		$pictures_current = array_chunk((array) $pictures, ($this->conf[$this->mode . '.']['rows'] * $this->conf[$this->mode . '.']['columns'])); // split array in parts for pagebrowser
		$this->overall = count($pictures); // count all pictures
		$pointer = ($this->piVars['listpointer'] > 0 ? $this->piVars['listpointer'] : 0); // pointer
		
		
			//var_dump($pictures);
		if (count($pictures_current[$pointer]) > 0) { // if there are pictures
			for ($i=0; $i<count($pictures_current[$pointer]); $i++) { // one loop for every picture in current folder
				// let's start
				$row = array ( // write $row for .field in ts AND for markers in html template
					'picture' => $pictures_current[$pointer][$i], // first entry of files array (e.g. fileadmin/pic1.jpg)
					'tstamp' => filemtime($pictures_current[$pointer][$i]), // timestamp of file
					'filename' => $this->div->fileInfo($pictures_current[$pointer][$i], 'filename'), // like pic
					'dirname' => $this->div->fileInfo($pictures_current[$pointer][$i], 'dirname'), // like fileadmin/pics
					'basename' => $this->div->fileInfo($pictures_current[$pointer][$i], 'basename'), // like pic.jpg
					'extension' => $this->div->fileInfo($pictures_current[$pointer][$i], 'extension'), // like jpg
					'currentfolder' => $this->div->fileInfo($pictures_current[$pointer][$i], 'currentfolder'), // like folder
					'picturehash' => $this->div->hashCode($pictures_current[$pointer][$i]), // like 12345678
					'pid_single' => ($this->conf['single.']['pid_single'] > 0 ? $this->conf['single.']['pid_single'] : $GLOBALS['TSFE']->id), // PID of single view
					'link_single' => tslib_pibase::pi_linkTP_keepPIvars_url(array('show' => $this->div->hashCode($pictures_current[$pointer][$i])), 1, 0, ($this->conf['single.']['pid_single'] > 0 ? $this->conf['single.']['pid_single'] : 0)) // link to single view
				);
				$metarow = $this->div->EXIForTXT($row['picture'], $this->conf[$this->mode . '.']['metainformation']); // get metainformation
				$row = array_merge((array) $row, (array) $metarow); // add array from txt or exif to normal row
				$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
				
				$this->markerArray = $this->div->markersClassStyle($i, $this->mode, $this->conf); // fill ###CLASS###
				if (($this->piVars['show'] == $this->div->hashCode($pictures_current[$pointer][$i])) || (!isset($this->piVars['show']) && $i == 0)) $this->markerArray['###CLASS###'] .= ' wtgallery_list_current'; // add string to div if current picture
				if (!empty($this->conf[$this->mode . '.']['width'])) $this->conf[$this->mode . '.']['image.']['file.']['width'] = $this->conf[$this->mode . '.']['width'];  // set width from config (e.g. flexform if not empty)
				if (!empty($this->conf[$this->mode . '.']['height'])) $this->conf[$this->mode . '.']['image.']['file.']['height'] = $this->conf[$this->mode . '.']['height'];  // set height from config (e.g. flexform if not empty)
				foreach ($this->conf[$this->mode . '.'] as $key => $value) { // one loop for every main level in typoscript (single.image, single.text, single.listviewlink, etc...)
					if ($key != 'pagebrowser' && !is_array($this->conf[$this->mode . '.'][$key])) { // don't use pagebrowser here but everything else
						$this->markerArray['###' . strtoupper($key) . '###'] = $this->cObj->cObjGetSingle($this->conf[$this->mode . '.'][$key], $this->conf[$this->mode.'.'][$key . '.']); // values from ts
					}
				}
				foreach ($row as $key => $value) { // one loop for every row entry
					$this->markerArray['###' . strtoupper($key) . '###'] = $value; // fill marker with value of row
				}
				
				$this->wrappedSubpartArray['###SINGLELINK###'][0] = '<a href="' . tslib_pibase::pi_linkTP_keepPIvars_url(array('show' => $this->div->hashCode($row['picture'])), 1, 0, ($this->conf['single.']['pid_single'] > 0 ? $this->conf['single.']['pid_single'] : 0)) . '">'; // Link with piVars "show"
				$this->wrappedSubpartArray['###SINGLELINK###'][1] = '</a>'; // postfix for linkwrap
				
				$this->hook_inner(); // add hook
				$content_item .= $this->div->rowWrapper($this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['item'], $this->markerArray, array(), $this->wrappedSubpartArray), $i, $this->mode, count($pictures_current[$pointer]), $this->conf); // add inner html to variable
			} 
		}
		
		$this->num = $i; // current pictures for pagebrowser
		$subpartArray['###CONTENT###'] = $content_item; // work on subpart 3
		$this->outerMarkerArray['###PAGEBROWSER###'] = $this->pagebrowser->start($this->conf, $this->piVars, $this->cObj, array('overall' => $this->overall, 'overall_cur' => ($this->conf[$this->mode . '.']['rows'] * $this->conf[$this->mode . '.']['columns']), 'pointer' => $pointer, 'perPage' => ($this->conf[$this->mode . '.']['rows'] * $this->conf[$this->mode . '.']['columns']))); // includes pagebrowser function
		$this->outerMarkerArray = array_merge((array) $this->markerArray, (array) $this->outerMarkerArray); // add all markers of last picture to the outermarker Array
		
		$this->hook_outer(); // add hook
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->outerMarkerArray, $subpartArray); // Get html template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		if (!empty($this->content) && $i > 0) return $this->content; // return HTML if $content is not empty and if there are pictures
	}	
	
	
	// Add outer Hook
	function hook_outer() {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['list_outer']) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['list_outer'] as $_funcRef) {
			  if ($_funcRef) \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $this);
		   }
		}
	}
	
	
	// Add inner Hook
	function hook_inner() {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['list_inner']) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['list_inner'] as $_funcRef) {
			  if ($_funcRef) \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $this);
		   }
		}
	}
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_list.php']);
}

?>
