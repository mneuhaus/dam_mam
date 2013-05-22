<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']["dam_mam"]);

if($_extConfig['no_upload']){
	// Remove some dam modules
	$modules = explode(",", $GLOBALS["TBE_MODULES"]["txdamM1"]);
	$GLOBALS["TBE_MODULES"]["txdamM1"] = implode(",", array_diff($modules, array("file", "tools", "info", "cmd")));
	$TCA['tx_dam']['txdamInterface'] = array(
		'index_fieldList' => 'title,keywords,description,caption,alt_text,file_orig_location,file_orig_loc_desc,ident,creator,publisher,copyright,instructions,date_cr,date_mod,loc_desc,loc_country,loc_city,language,category',
		'info_fieldList_add' => '',
		'info_displayFields_exclude' => 'fe_group,hidden,starttime,endtime,file_orig_loc_desc,file_orig_location',
		'info_displayFields_isNonEditable' => 'category,media_type,thumb,file_usage',
	);
}

#tx_dam::register_selection ('txdammamMAMProperties', 'EXT:dam_mam/dam/TreeClasses/class.tx_dammam_selectionMAMProperies.php:&tx_dammam_selectionMAMProperies');

$tempColumns = array (
    'tx_dammam_mamuuid' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_mamuuid',
        'config' => array (
            'type' => 'input',
            'size' => '30',
        )
    ),
	'tx_dammam_mime_type' => array (
	    'exclude' => 0,
	    'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_mime_type',
	    'config' => array (
	        'type' => 'input',
	        'size' => '30',
	    )
	),
	'tx_dammam_file_type' => array (
	    'exclude' => 0,
	    'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_file_type',
	    'config' => array (
	        'type' => 'input',
	        'size' => '30',
	    )
	),
	'tx_dammam_file_name' => array (
	    'exclude' => 0,
	    'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_file_name',
	    'config' => array (
	        'type' => 'input',
	        'size' => '30',
	    )
	),
	'tx_dammam_color_space' => array (
	    'exclude' => 0,
	    'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_color_space',
	    'config' => array (
	        'type' => 'input',
	        'size' => '30',
	    )
	),
	'tx_dammam_hpixels' => array (
	    'exclude' => 0,
	    'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_hpixels',
	    'config' => array (
	        'type' => 'input',
	        'size' => '30',
	    )
	),
	'tx_dammam_vpixels' => array (
	    'exclude' => 0,
	    'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.tx_dammam_vpixels',
	    'config' => array (
	        'type' => 'input',
	        'size' => '30',
	    )
	),
	'tx_dammam_related_files' => txdam_getMediaTCA('media_field', 'tx_dammam_related_files'),
	/*
	 * LANGUAGE
	 */
	'tx_dammam_language' => array(
		'exclude' => '1',
		'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
		'config' => array(

   //          'type' => 'group',
   //          'internal_type' => 'db',
   //          'allowed' => 'sys_language',
   //          'size' => 5,
   //          'minitems' => 0,
   //          'maxitems' => 10,

            'type' => 'select',
            'foreign_table' => 'sys_language',
            'size' => 5,
            'minitems' => 0,
            'maxitems' => 10,
			'items' => array(
				array($_extConfig['default_language_label'], 0)
			)
		)
	),
	'tx_dammam_bedienungsanleitung' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_bedienungsanleitung',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_de' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_de',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_en' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_en',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_fr' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_fr',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_es' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_es',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_it' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_it',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_nl' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_nl',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_ru' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_ru',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_pl' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_pl',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_hu' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_hu',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_cs' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_cs',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_zhen' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_zhen',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_dammam_assetname_zh' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:dam_mam/locallang_db.xml:tx_dammam_assetname_zh',
		'config' => array (
			'type' => 'input',
			'size' => '30',
		)
	),
);

require_once(t3lib_extMgm::extPath("dam_mam") . '/dam/class.user_dammam_related_files.php');
$tempColumns['tx_dammam_related_files']['label'] = 'LLL:EXT:dam_mam/locallang_db.xml:tx_dam.related_files';
$tempColumns['tx_dammam_related_files']['config']['type'] = 'user';
$tempColumns['tx_dammam_related_files']['config']['userFunc'] = 'user_dammam_related_files->user_related_files';

t3lib_div::loadTCA('tx_dam');
t3lib_extMgm::addTCAcolumns('tx_dam',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tx_dam','--div--;LLL:EXT:dam_mam/locallang_db.xml:tx_dammam.mam, tx_dammam_mamuuid;;;;1-1-1, tx_dammam_mime_type, tx_dammam_file_type, tx_dammam_file_name, tx_dammam_color_space, tx_dammam_hpixels, tx_dammam_vpixels, tx_dammam_related_files, tx_dammam_language, tx_dammam_bedienungsanleitung, tx_dammam_assetname_de, tx_dammam_assetname_en, , tx_dammam_assetname_fr, tx_dammam_assetname_es, tx_dammam_assetname_it, tx_dammam_assetname_nl, tx_dammam_assetname_ru, tx_dammam_assetname_pl, tx_dammam_assetname_hu, tx_dammam_assetname_cs, tx_dammam_assetname_zhen, tx_dammam_assetname_zh');

?>