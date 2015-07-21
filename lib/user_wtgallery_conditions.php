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

	
	
/**
 * check if wt_gallery is loaded and used on current page in current language
 *
 * @return	boolean		true/false
 */
function user_wtgallery_oncurrentpage() {
	// config
	$languid = intval($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid']) > 0 ? $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] : 0; // current language uid
	$i = 0; $found = 0;
	
	// let's go
	// 1. check if plugin is on current page in current lang
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
		'tt_content.pi_flexform',
		'tt_content',
		$where_clause = 'pid = ' . intval($GLOBALS['TSFE']->id) . ' AND sys_language_uid = ' . $languid . ' AND deleted = 0 AND hidden = 0',
		$groupBy = '',
		$orderBy = '',
		$limit = '1'
	);
	if ($res) { // If there is a result
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { // One loop for wt_gallery entry on current page in current lang
			if ($row['pi_flexform']) { // if there are values in the flexform
				$flexform_arr = t3lib_div::xml2array($row['pi_flexform']); // change xml to an array
				$mode_str = $flexform_arr['data']['main']['lDEF']['mode']['vDEF']; // get mode of entry
				$mode = t3lib_div::trimExplode(',', $mode_str, 1); // change mode to an array
				if (in_array('cooliris', $mode)) $found = 1; // found something
				$i++; // increase counter
			}
		}
	}
	if ($found == 1) return true; // yes to xml
	else return false;
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/user_wtgallery_conditions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/user_wtgallery_conditions.php']);
}
?>