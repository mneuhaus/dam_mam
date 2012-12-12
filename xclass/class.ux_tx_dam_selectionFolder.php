<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2006 Rene Fritz (r.fritz@colorcube.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Contains standard selection trees/rules.
 * Part of the DAM (digital asset management) extension.
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @package DAM-Component
 * @subpackage  Selection
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   81: class tx_dam_selectionFolder extends t3lib_folderTree
 *  122:     function tx_dam_selectionFolder()
 *  140:     function getId ($row)
 *  152:     function getJumpToParam($row, $command='SELECT')
 *  166:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  192:     function wrapTitle($title,$row,$bank=0)
 *  215:     function getControl($title,$row)
 *  242:     function printTree($treeArr='')
 *  293:     function setMounts($mountpoints)
 *  306:     function getTreeTitle()
 *  315:     function getDefaultIcon()
 *  325:     function getTreeName()
 *
 *              SECTION: DAM specific functions
 *  344:     function selection_getItemTitle($id)
 *  356:     function selection_getItemIcon($id, $value)
 *  377:     function selection_getQueryPart($queryType, $operator, $cat, $id, $value, &$damObj)
 *
 *              SECTION: element browser specific functions
 *  406:     function eb_wrapTitle($title,$row)
 *  421:     function eb_PM_ATagWrap($icon,$cmd,$bMark='')
 *  437:     function eb_printTree($treeArr='')
 *  496:     function ext_isLinkable($v)
 *
 * TOTAL FUNCTIONS: 18
 * (This index is automatically created/updated by the script "update-class-index")
 *
 */


/**
 * folder tree class
 *
 * This is customized to behave like a selection class.
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @package DAM-Component
 * @subpackage  Selection
 */
class ux_tx_dam_selectionFolder extends tx_dam_selectionFolder  {
	/**
	 * Function, processing the query part for selecting/filtering records in DAM
	 * Called from DAM
	 *
	 * @param	string		Query type: AND, OR, ...
	 * @param	string		Operator, eg. '!=' - see DAM Documentation
	 * @param	string		Category - corresponds to the "treename" used for the category tree in the nav. frame
	 * @param	string		The select value/id
	 * @param	string		The select value (true/false,...)
	 * @param	object		Reference to the parent DAM object.
	 * @return	string
	 * @see tx_dam_selection::getWhereClausePart()
	 */
	public function selection_getQueryPart($queryType, $operator, $cat, $id, $value, &$damObj) {
		$query = $damObj->sl->getFieldMapping('tx_dam', 'file_path');
		if ($queryType === 'NOT') {
			$query .= ' NOT';
		}
		$likeStr = $GLOBALS['TYPO3_DB']->escapeStrForLike(tx_dam::path_makeRelative($id), 'tx_dam');
		$likeStr = preg_replace('/[^\w\/\-\_]/', '%', $likeStr);
		$query .= ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($likeStr . '%', 'tx_dam');
		
		return array($queryType,$query);
	}
}

?>