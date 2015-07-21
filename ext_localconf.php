<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

include_once(t3lib_extMgm::extPath('wt_gallery') . 'lib/user_wtgallery_conditions.php'); // Some userFuncs (could be used in conditions)

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_wtgallery_pi1.php', '_pi1', 'list_type', 0);

// Hooks for realurl manipulation
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['encodeSpURL_postProc'][] = 'EXT:wt_gallery/class.tx_wtgallery_realurl.php:tx_wtgallery_realurl->encode';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['decodeSpURL_preProc'][] = 'EXT:wt_gallery/class.tx_wtgallery_realurl.php:tx_wtgallery_realurl->decode';
?>