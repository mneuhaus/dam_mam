<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "dam_mam".
 *
 * Auto generated 05-12-2012 14:00
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'MAM CMS Connecter',
	'description' => '',
	'category' => 'module',
	'author' => 'Marc Neuhaus',
	'author_email' => 'mneuhaus@famelo.com',
	'shy' => '',
	'dependencies' => 'static_info_tables,dam,dam_ttcontent,dam_filelinks,dam_pages,scheduler',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.10',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.6-0.0.0',
			'typo3' => '4.5.0-0.0.0',
			'static_info_tables' => '',
			'dam' => '1.2.3',
			'dam_ttcontent' => '',
			'dam_filelinks' => '',
			'dam_pages' => '',
			'scheduler' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'dam_ttnews' => '',
			'dam_tv_connector' => '',
			'devlog' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:29:{s:9:"ChangeLog";s:4:"1233";s:16:"ext_autoload.php";s:4:"52a8";s:21:"ext_conf_template.txt";s:4:"13c0";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"8bdf";s:14:"ext_tables.php";s:4:"8cc9";s:14:"ext_tables.sql";s:4:"90c9";s:13:"locallang.xml";s:4:"12c8";s:16:"locallang_db.xml";s:4:"6e0c";s:10:"README.txt";s:4:"ee2d";s:12:"tsconfig.txt";s:4:"659d";s:16:"css/metadata.css";s:4:"95ca";s:33:"dam/class.tx_dammam_show_item.php";s:4:"494b";s:39:"dam/class.user_dammam_related_files.php";s:4:"fd43";s:57:"dam/TreeClasses/class.tx_dammam_selectionMAMProperies.php";s:4:"6c39";s:14:"doc/manual.pdf";s:4:"51ad";s:14:"doc/manual.sxw";s:4:"0b87";s:19:"doc/wizard_form.dat";s:4:"d4c0";s:20:"doc/wizard_form.html";s:4:"6544";s:33:"hooks/class.user_cssfilelinks.php";s:4:"9d25";s:26:"models/class.BaseModel.php";s:4:"267c";s:33:"models/class.DamCategoryModel.php";s:4:"4e09";s:25:"models/class.DamModel.php";s:4:"61ff";s:47:"scheduler/class.tx_dammam_garbage_collector.php";s:4:"933a";s:71:"scheduler/class.tx_dammam_garbage_collector_additionalfieldprovider.php";s:4:"a382";s:34:"scheduler/class.tx_dammam_sync.php";s:4:"b53d";s:58:"scheduler/class.tx_dammam_sync_additionalfieldprovider.php";s:4:"e7ab";s:37:"xclass/class.ux_tx_dam_actioncall.php";s:4:"fd7b";s:38:"xclass/class.ux_tx_dam_listrecords.php";s:4:"1f12";}',
	'suggests' => array(
	),
);

?>