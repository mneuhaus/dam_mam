<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$_extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']["dam_mam"]);

if($_extConfig['no_upload']){
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:dam_mam/tsconfig.txt">');

	// XClass Extensions
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam/lib/class.tx_dam_actioncall.php'] = t3lib_extMgm::extpath("dam_mam", "xclass/class.ux_tx_dam_actioncall.php");
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam/lib/class.tx_dam_listrecords.php'] = t3lib_extMgm::extpath("dam_mam", "xclass/class.ux_tx_dam_listrecords.php");
	
	// Unset useless TreeClasses
	unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dam']['selectionClasses']["txdamStatus"]);
	unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dam']['selectionClasses']["txdamIndexRun"]);
	
	foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] as $key => $value) {
	if($value == 'EXT:dam/binding/be/class.tx_dam_show_item.php:&tx_dam_show_item')
		$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/show_item.php']['typeRendering'][$key] = 'EXT:dam_mam/dam/class.tx_dammam_show_item.php:&tx_dammam_show_item';
	}
}
$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dam/components/class.tx_dam_selectionFolder.php'] = t3lib_extMgm::extpath("dam_mam", "xclass/class.ux_tx_dam_selectionFolder.php");

$TYPO3_CONF_VARS['EXTCONF']['css_filelinks']['pi1_hooks']['getFilesForCssUploads']="EXT:dam_mam/hooks/class.user_cssfilelinks.php:user_cssfilelinks";
$TYPO3_CONF_VARS['EXTCONF']['css_filelinks']['pi1_hooks_more']['fillFileMarkers'][] = 'EXT:dam_mam/hooks/class.user_cssfilelinks.php:user_cssfilelinks';

$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_dammam_sync'] = array(
	'extension' => $_EXTKEY,
	'title' => 'MAM CMS Connecter',
	'description' => '',
	'additionalFields' => 'tx_dammam_sync_additionalfieldprovider'
);

$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_dammam_garbage_collector'] = array(
	'extension' => $_EXTKEY,
	'title' => 'MAM CMS Garbage Collector',
	'description' => '',
	'additionalFields' => 'tx_dammam_garbage_collector_additionalfieldprovider'
);

$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_dammam_usage_exporter'] = array(
	'extension' => $_EXTKEY,
	'title' => 'MAM CMS Usage Exporter',
	'description' => '',
	'additionalFields' => 'tx_dammam_usage_exporter_additionalfieldprovider'
);

$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['tx_dammam_relation_updater'] = array(
	'extension' => $_EXTKEY,
	'title' => 'MAM CMS Relation Updater',
	'description' => ''
);

include_once(t3lib_extMgm::extPath('dam_mam', 'models/class.BaseModel.php'));
include_once(t3lib_extMgm::extPath('dam_mam', 'models/class.DamModel.php'));
include_once(t3lib_extMgm::extPath('dam_mam', 'models/class.DamCategoryModel.php'));
include_once(t3lib_extMgm::extPath('dam', 'components/class.tx_dam_dbTriggerMediaTypes.php'));

// Make DAM Render Thumbnails for other filetypes
$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'].= "ai,psd,eps,EPS,PSD,indd,INDD,swf,txt,tif,docx,pdf,mov";

t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
	tt_content.uploads.20{
		dam_mam{
			files = <ul>|</ul>
			file = <li><a href="###URL###">###LANGUAGE_ICONS######FILE_NAME###</a></li>
		}
		'.$tempEditIcons.'
	}

	tt_content.uploads.20.layout.file = <div class="###CLASS###"><span><a href="###URL###">###TITLE###</a> ###FILESIZE### ###CRID### ###MYMARK###</span><span>###DESCRIPTION###</span> ###RELATED_FILES###</div>
',43);
