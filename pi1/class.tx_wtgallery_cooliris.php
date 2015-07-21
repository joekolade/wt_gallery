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

class tx_wtgallery_cooliris extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_single.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	var $mode = 'cooliris'; // mode name
	
	function start($conf, $piVars, $cObj) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$fullscreen = $this->conf[$this->mode . '.']['allow_fullscreen'] == 1 ? 'true' : 'false'; // Fullscreen button
		$scriptaccess = $this->conf[$this->mode . '.']['allow_scriptaccess'] == 1 ? 'always' : 'false'; // Script access button
		
		// let's go
		// check if there are pictures in current folder
		$startpath = $this->div->validatePicturePath($this->piVars['category'] ? $this->div->hash2folder($this->piVars['category'], $this->conf['main.']['path']) : $this->conf['main.']['path']); // startpath from piVars or from ts
		$pictures = $this->div->getFiles($this->conf, $startpath, $this->conf[$this->mode . '.']['order'], $this->conf[$this->mode . '.']['limit']); // get all pictures from current folder
		
		if (count($pictures) > 0) { // if there are pictures in current folder
			$rssurl_linkconf = array (
				'parameter' => $GLOBALS['TSFE']->id, 
				'additionalParams' => '&type=3135' . ($this->piVars['category'] ? '&' . $this->prefixId . '[category]=' . $this->piVars['category'] : '') . ($this->conf[$this->mode . '.']['flashvars'] ? $this->conf[$this->mode . '.']['flashvars'] : ''), 
				'useCacheHash' => 1, 
				'returnLast' => 'url'
			);
			$rssurl = t3lib_div::getIndpEnv('TYPO3_SITE_URL'); // start with the domain
			$rssurl .= $this->cObj->typolink('x', $rssurl_linkconf); // generate link
			
			// add html code for showing swf
			$this->content = '
				<object id=\'coolirisOuter\' classid=\'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\' width=\'' . intval($this->conf[$this->mode . '.']['window_width']) . '\' height=\'' . intval($this->conf[$this->mode . '.']['window_height']) . '\'>
					<param name=\'movie\' value=\'' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $GLOBALS['TSFE']->tmpl->getFileName($this->conf['cooliris.']['swf']) . '\' />
					<param name=\'flashvars\' value=\'feed=' . $rssurl . '\' />
					<param name=\'allowFullScreen\' value=\'' . $fullscreen . '\' />
					<param name=\'allowScriptAccess\' value=\'' . $scriptaccess . '\' />
					<!--[if !IE]>-->
						<object id=\'coolirisInner\' type=\'application/x-shockwave-flash\' data=\'' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $GLOBALS['TSFE']->tmpl->getFileName($this->conf['cooliris.']['swf']) . '\' width=\'' . intval($this->conf[$this->mode . '.']['window_width']) . '\' height=\'' . intval($this->conf[$this->mode . '.']['window_height']) . '\'>
							<param name=\'flashvars\' value=\'feed=' . $rssurl . '\' />
							<param name=\'allowFullScreen\' value=\'' . $fullscreen . '\' />
							<param name=\'allowScriptAccess\' value=\'' . $scriptaccess . '\' />
					<!--<![endif]-->
					<div class="wt_gallery_noflash">' . htmlentities($this->conf[$this->mode . '.']['noflash_message']) . '</div>
					<!--[if !IE]>-->
						</object>
					<!--<![endif]-->
				</object>
			';
		}
		
		if (!empty($this->content)) return $this->content; // return HTML if $content is not empty
	}	
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_cooliris.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_cooliris.php']);
}

?>