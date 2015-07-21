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
require_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/class.tx_wtgallery_div.php'); // load div class
require_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/class.tx_wtgallery_dynamicmarkers.php'); // file for dynamicmarker functions
require_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/class.tx_wtgallery_category_pagebrowser.php'); // file for categorybrowser function

class tx_wtgallery_category extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_category.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	var $mode = 'category'; // kind of mode
	
	function start($conf, $piVars, $cObj) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->pagebrowser = t3lib_div::makeInstance('tx_wtgallery_category_pagebrowser'); // Create new instance for categorybrowser function
		$this->tmpl = $this->markerArray = $this->outerMarkerArray = array(); $content_item = ''; // init
		$this->tmpl[$this->mode]['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###WTGALLERY_CATEGORY###'); // Load HTML Template
		$this->tmpl[$this->mode]['item'] = $this->cObj->getSubpart($this->tmpl[$this->mode]['all'],'###ITEM###'); // work on subpart 2

		// let's go
		$startpath = $this->div->validatePicturePath($this->piVars[$this->mode] ? $this->div->hash2folder($this->piVars[$this->mode], $this->conf['main.']['path']) : $this->conf['main.']['path']); // startpath from piVars or from ts
		if ($this->conf[$this->mode . '.']['forceFolder'] != '') $startpath = $this->div->validatePicturePath($this->conf[$this->mode . '.']['forceFolder']); // overwrite startpath if forceFolder is set in constants
		$folders = $this->div->sorting4folders($startpath, $this->conf[$this->mode . '.']['order'], $this->conf[$this->mode . '.']['limit']); // Get all subfolders in the picture folder
		$folders_current = array_chunk((array) $folders, ($this->conf[$this->mode . '.']['rows'] * $this->conf[$this->mode . '.']['columns'])); // split array in parts for pagebrowser
		$this->overall = count($folders); // count all pictures
		$pointer = ($this->piVars['categorypointer'] > 0 ? $this->piVars['categorypointer'] : 0); // pointer
		
		if (count($folders_current[$pointer]) > 0) { // if there are folders
			for ($i=0; $i<count($folders_current[$pointer]); $i++) { // one loop for every folder in current folder
				// let's start
				$picture = $this->div->getFiles($this->conf, $startpath.$folders_current[$pointer][$i].'/', $this->conf[$this->mode . '.']['previewpicture_order'], 1); // get a picture from category
				$row = array ( // write $row for .field in ts AND for markers in html template
					'picture' => $picture[0], // first entry of files array (e.g. fileadmin/pic1.jpg)
					'tstamp' => filemtime($picture[0]), // timestamp of file
					'filename' => $this->div->fileInfo($picture[0], 'filename'), // like pic
					'dirname' => $this->div->fileInfo($picture[0], 'dirname'), // like fileadmin/pics
					'basename' => $this->div->fileInfo($picture[0], 'basename'), // like pic.jpg
					'extension' => $this->div->fileInfo($picture[0], 'extension'), // like jpg
					'currentfolder' => $this->div->fileInfo($picture[0], 'currentfolder'), // like folder
					'link_list' => $this->cObj->typolink('x', array('parameter' => $GLOBALS['TSFE']->id, 'additionalParams' => '&'.$this->prefixId.'[category]='.$this->div->fileInfo($picture[0], 'dirname', 1), 'useCacheHash' => 1, 'returnLast' => 'url') ) // , 'ATagParams' => 'target="_self"'
				);
				$metarow = $this->div->EXIForTXT($row['picture'], $this->conf[$this->mode . '.']['metainformation']); // get metainformation
				$catTXTrow = $this->div->readTXT4cat($row); // get txt files for every category
				$row = array_merge((array) $row, (array) $metarow, (array) $catTXTrow); // add array from txt or exif to normal row
				$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
				
				$this->markerArray = $this->div->markersClassStyle($i, $this->mode, $this->conf); // fill ###CLASS### and ###STYLE###
				if (!empty($this->conf[$this->mode . '.']['width'])) $this->conf[$this->mode . '.']['image.']['file.']['width'] = $this->conf[$this->mode . '.']['width'];  // set width from config (e.g. flexform if not empty)
				if (!empty($this->conf[$this->mode . '.']['height'])) $this->conf[$this->mode . '.']['image.']['file.']['height'] = $this->conf[$this->mode . '.']['height'];  // set height from config (e.g. flexform if not empty)
				foreach ($this->conf[$this->mode . '.'] as $key => $value) { // one loop for every main level in typoscript (single.image, single.text, single.listviewlink, etc...)
					if ($key != 'pagebrowser' && !is_array($this->conf[$this->mode . '.'][$key])) { // don't use pagebrowser here but everything else
						$this->markerArray['###' . strtoupper($key) . '###'] = $this->cObj->cObjGetSingle($this->conf[$this->mode . '.'][$key], $this->conf[$this->mode.'.'][$key . '.']); // values from ts
					}
				}
				foreach ($row as $key => $value) { // one loop for every row entry
					$this->markerArray['###'.strtoupper($key).'###'] = $value; // fill marker with value of row
				}
		
				$this->wrappedSubpartArray['###CATEGORYLINK###'] = $this->cObj->typolinkWrap( array('parameter' => $GLOBALS['TSFE']->id, 'additionalParams' => '&'.$this->prefixId.'[category]='.$this->div->fileInfo($picture[0], 'dirname', 1), 'useCacheHash' => 1) ); // Link to same page with current folder
				
				$this->hook_inner(); // add hook
				$content_item .= $this->div->rowWrapper($this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['item'], $this->markerArray, array(), $this->wrappedSubpartArray), $i, $this->mode, count($folders_current[$pointer]), $this->conf); // add inner html to variable
			} 
		}
		$this->num = $i; // current pictures for pagebrowser
		$subpartArray['###CONTENT###'] = $content_item; // work on subpart 3
		
		// fill outer markers
		$pbarray = array( // prepare array for pagebrowser
			'overall' => $this->overall, // all numbers of categories
			'overall_cur' => ($this->conf[$this->mode . '.']['rows'] * $this->conf[$this->mode . '.']['columns']), // categories on each page
			'pointer' => $pointer, // pointer
			'perPage' => ($this->conf[$this->mode . '.']['rows'] * $this->conf[$this->mode . '.']['columns']), // categories on each page
			'folders' => $folders, // folder array
			'startpath' => $startpath
		);
		$this->outerMarkerArray['###PAGEBROWSER###'] = $this->pagebrowser->start($this->conf, $this->piVars, $this->cObj, $pbarray); // include categorybrowser
		
		$this->hook_outer(); // add hook
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->outerMarkerArray, $subpartArray); // Get html template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", '', $this->content); // Finally clear not filled markers
		if (!empty($this->content) && ($i > 0 || !empty($this->piVars[$this->mode]))) return $this->content; // return HTML if $content is not empty AND ( if there are pictures OR category was chosen )
	}	
	
	
	// Add outer Hook
	function hook_outer() {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['category_outer']) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['category_outer'] as $_funcRef) {
			  if ($_funcRef) t3lib_div::callUserFunction($_funcRef, $this);
		   }
		}
	}
	
	
	// Add inner Hook
	function hook_inner() {
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['category_inner']) {
		   foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['category_inner'] as $_funcRef) {
			  if ($_funcRef) t3lib_div::callUserFunction($_funcRef, $this);
		   }
		}
	}
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_category.php']);
}

?>