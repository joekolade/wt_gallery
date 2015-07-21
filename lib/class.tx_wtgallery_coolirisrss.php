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

class tx_wtgallery_coolirisrss extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_pi1.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	var $mode = 'cooliris'; // mode name
	
	function start($conf, $piVars, $cObj) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->tmpl = $this->markerArray = $this->outerMarkerArray = $subpartArray = array(); $content_item = ''; // init
		$this->tmpl[$this->mode]['all'] = trim($this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.'][$this->mode]), '###WTGALLERY_COOLIRIS_RSS###')); // Load HTML Template
		$this->tmpl[$this->mode]['item'] = $this->cObj->getSubpart($this->tmpl[$this->mode]['all'],'###ITEM###'); // work on subpart 2
		
		// let's go
		if (!empty($this->conf['main.']['path'])) { // if startpath was set via typoscript
			$startpath = $this->div->validatePicturePath($this->piVars['category'] ? $this->div->hash2folder($this->piVars['category'], $this->conf['main.']['path']) : $this->conf['main.']['path']); // startpath from piVars or from ts
			$pictures = $this->div->getFiles($this->conf, $startpath, $this->conf[$this->mode.'.']['order'], $this->conf[$this->mode.'.']['limit']); // get all pictures from current folder
			
			if (count($pictures) > 0) { // if there are pictures
				for ($i=0; $i<count($pictures); $i++) { // one loop for every picture in current folder
					// let's start
					$row = array ( // write $row for .field in ts AND for markers in html template
						'picture' => $pictures[$i], // first entry of files array (e.g. fileadmin/pic1.jpg)
						'tstamp' => filemtime($pictures[$i]), // timestamp of file
						'filename' => $this->div->fileInfo($pictures[$i], 'filename'), // like pic
						'dirname' => $this->div->fileInfo($pictures[$i], 'dirname'), // like fileadmin/pics
						'basename' => $this->div->fileInfo($pictures[$i], 'basename'), // like pic.jpg
						'extension' => $this->div->fileInfo($pictures[$i], 'extension'), // like jpg
						'currentfolder' => $this->div->fileInfo($pictures[$i], 'currentfolder'), // like folder
						'picturehash' => t3lib_div::md5int($pictures[$i]), // like 12345678
					);
					$metarow = $this->div->EXIForTXT($row['picture'], $this->conf[$this->mode . '.']['metainformation']); // get metainformation
					$row = array_merge((array) $row, (array) $metarow); // add array from txt or exif to normal row
					$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
					
					if (!empty($this->conf[$this->mode.'.']['width'])) $this->conf[$this->mode.'.']['image.']['file.']['width'] = $this->conf[$this->mode.'.']['width'];  // set width from config (e.g. flexform if not empty)
					if (!empty($this->conf[$this->mode.'.']['height'])) $this->conf[$this->mode.'.']['image.']['file.']['height'] = $this->conf[$this->mode.'.']['height'];  // set height from config (e.g. flexform if not empty)
					if (!is_array($this->conf[$this->mode.'.']['image']))
						$this->markerArray['###IMAGE###'] = $this->cObj->cObjGetSingle($this->conf[$this->mode.'.']['image'], $this->conf[$this->mode.'.']['image.']); // values from ts
					foreach ($row as $key => $value) { // one loop for every row entry
						$this->markerArray['###'.strtoupper($key).'###'] = $value; // fill marker with value of row
					}
					
					$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['item'], $this->markerArray, array(), $this->wrappedSubpartArray); // add inner html to variable
				} 
			}
			$subpartArray['###CONTENT###'] = $content_item; // work on subpart 3
			$this->outerMarkerArray['###TITLE###'] = $this->conf[$this->mode.'.']['title']; // Add title to RSS
			$this->outerMarkerArray['###URL###'] = $this->conf[$this->mode.'.']['url']; // Add url to RSS
			$this->outerMarkerArray['###DESCRIPTION###'] = $this->conf[$this->mode.'.']['description']; // Add description to RSS
			
			
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl[$this->mode]['all'], $this->outerMarkerArray, $subpartArray); // Get html template
			$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
			$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
			if (!empty($this->content) && count($pictures) > 0) return $this->content; // return HTML if $content is not empty and if there are pictures
			else return $this->pi_getLL('wtgallery_ll_coolirisError', 'no pictures to list on current url'); // return error message for dynamic RSS
		} else { // if startpath was not set via typoscript
			return $this->pi_getLL('wtgallery_ll_coolirisErrorNoPath', 'Please set the startpath via typoscript and not only in the plugin - this is needed for cooliris!'); // return error message for dynamic RSS
		}
	}	
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_coolirisrss.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_coolirisrss.php']);
}

?>