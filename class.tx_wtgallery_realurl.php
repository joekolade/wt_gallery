<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Alex Kellner <alexander.kellner@einpraegsam.net>
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



/**
 * Plugin 'wt gallery' for the 'wt_gallery' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	tx_wtgallery_realurl
 */
class tx_wtgallery_realurl {

	public $pid = 0;
	public $startpath = 'fileadmin/';
	
	
	/**
	 * Change the URL of every link in FE short before given to TYPO3 FE parser
	 *
	 * @param	array		$params: Params
	 * @param	array		$ref: Parent Object
	 * @return	void
	 */
	public function encode(&$params, &$ref) {
		// config
		$this->pid = $ref->pObj->id;
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wt_gallery']); // Get backandconfig
		$newFileArray = array();
		
		// start
		if ($confArr['readableURL'] != 1 || !$this->galOnCurPage()) { // if not activated or wt_gallery is not within this page
			return false; // stop process now
		}
		
		// 1. change category: integer to real name
		$folderArray = $this->folderChange($this->startpath);
		foreach ((array) $folderArray as $path => $hash) { // one loop for every folder in the fileadmin
			$folder_arr = t3lib_div::trimExplode('/', $path, 1); // split path on /
			$last_folder = array_pop($folder_arr); // get last path element
			$urlNew = preg_replace('|\b' . $hash . '\b|', urlencode($last_folder), $params['URL']); // rewrite before url output
			if ($urlNew != $params['URL']) { // there was a change
				$folderPath = $path; // store for later
			}
			$params['URL'] = $urlNew;
		}
		
		// 2. change show: integer to real name
		$fileArray = t3lib_div::getFilesInDir($folderPath, 'jpg, jpeg, gif, png', 1, 1); // Get all pictures of current folder
		foreach ((array) $fileArray as $file) { // one loop for every file in current folder
			$file_arr = pathinfo($file);
			$params['URL'] = preg_replace('|\b' . t3lib_div::md5int($file) . '\b|', urlencode($file_arr['filename']), $params['URL']); // rewrite before url output
		}
	}
	
	
	/**
	 * Change the URL of the given GET param before given back to TYPO3
	 *
	 * @param	array		$params: Params
	 * @param	array		$ref: Parent Object
	 * @return	void
	 */
	public function decode(&$params, &$ref) {
		// config
		$this->pid = $ref->pObj->id;
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wt_gallery']); // Get backandconfig
		$newFileArray = array();
		
		// start
		if ($confArr['readableURL'] != 1 || !$this->galOnCurPage()) { // if not activated or wt_gallery is not within this page
			return false; // stop process now
		}
		
		// 1. change category: real name to integer
		$folderArray = $this->folderChange($this->startpath);
		foreach ((array) $folderArray as $path => $hash) { // one loop for every folder in the fileadmin
			$folder_arr = t3lib_div::trimExplode('/', $path, 1); // split path on /
			$last_folder = array_pop($folder_arr); // get last path element
			$urlNew = preg_replace('|\b' . preg_quote(urlencode($last_folder)) . '\b|', $hash, $params['URL']); // rewrite before url output
			if ($urlNew != $params['URL']) { // there was a change
				$folderPath = $path; // store for later
			}
			$params['URL'] = $urlNew;
		}
		
		// 2. change show: real name to integer
		$fileArray = t3lib_div::getFilesInDir($folderPath, 'jpg, jpeg, gif, png', 1, 1); // Get all pictures of current folder
		foreach ((array) $fileArray as $file) { // one loop for every file in current folder
			$file_arr = pathinfo($file);
			$params['URL'] = preg_replace('|\b' . preg_quote(urlencode($file_arr['filename'])) . '\b|', t3lib_div::md5int($file), $params['URL']); // rewrite before url output
		}
	}
	
	
	/**
	 * Is wt_gallery on the current page?
	 *
	 * @return	boolean
	 */
	public function galOnCurPage() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( // DB query
			'uid',
			'tt_content',
			$where_clause = 'pid = ' . intval($this->pid) . ' AND list_type = "wt_gallery_pi1" AND deleted = 0 AND hidden = 0',
			$groupBy = '',
			$orderBy = '',
			$limit = 1
		);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); // Result in array
			if ($row['uid'] > 0) {
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * Is wt_gallery on the current page?
	 *
	 * @param	string		$startpath: Start path like fileadmin/
	 * @return	array		$newArray: Array with folders and its hash code
	 */
	public function folderChange($startpath) {
		// config
		$subfolder = '';
		
		// let's go
		$folderArray = $newArray = array(); // init empty array
		$folderArray = t3lib_div::getAllFilesAndFoldersInPath($folderArray, t3lib_div::getFileAbsFileName($startpath), 'wt_gallery', 1); // get all folders of the startpath in an array
		$folderArray = array_flip($folderArray); // flip array
		
		foreach ((array) $folderArray as $key => $value) { // one loop for every array content
		
			if (substr($key, -1) === '/') {// if last sign is '/'
				$key = substr($key, 0, -1); // delete last sign
			}
			
			if (t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' != t3lib_div::getIndpEnv('TYPO3_SITE_URL')) { // if request_host is different to site_url (TYPO3 runs in a subfolder)
				$subfolder = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/', '', t3lib_div::getIndpEnv('TYPO3_SITE_URL')); // get the folder (like "subfolder/")
			} 
			
			$newArray[str_replace(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/' . $subfolder, '', $key)] = t3lib_div::md5int(str_replace(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/' . $subfolder, '', $key)); // rewrite array like 12345 => fileadmin/pics
		}
		
		if (!empty($newArray)) return $newArray;
	}
	
	
	/**
	 * Change the URL of the given GET param before given back to TYPO3
	 *
	 * @param	array		$param: should be "category" or "show"
	 * @return	integer		$value: value of param
	 */
	public function getGETparam($param = 'category') {
		$curURL = $_SERVER['QUERY_STRING'];
		$urlParts = t3lib_div::trimExplode('&', $curURL, 1);
		foreach ((array) $urlParts as $part) {
			if (stristr($part, '[' . $param . ']')) {
				$value = str_replace(array('tx_wtgallery_pi1[' . $param . ']='), '', $part);
			}
		}
		return $value;
	}
   
}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/class.tx_wtgallery_realurl.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/class.tx_wtgallery_realurl.php']);
}

?>