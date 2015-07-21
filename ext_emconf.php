<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "wt_gallery".
 *
 * Auto generated 20-07-2015 13:52
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'wt_gallery',
	'description' => 'Easy to use gallery with separate single- , category- and listview. Configurable via typoscript, show EXIF or TXT information, Cooliris (Piclens) and Imagelightbox integration, see screenshots in manual!',
	'category' => 'plugin',
	'version' => '4.0.0',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearcacheonload' => 0,
	'author' => 'Joe Schaefer, Miroslav Milev, Alex Kellner',
	'author_email' => 'schaefer@scw.info, milev@scw.info, alexander.kellner@einpraegsam.net',
	'author_company' => '',
	'constraints' => 
	array (
		'depends' => 
		array (
			'php' => '5.3.7-0.0.0',
			'typo3' => '6.0.0-0.0.0',
			'wt_doorman' => '0.2.1-',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

