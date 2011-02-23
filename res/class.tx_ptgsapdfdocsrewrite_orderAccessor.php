<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Daniel Lienert (t3extensions@punkt.de)
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

class tx_ptgsapdfdocsrewrite_orderAccessor {
	
/**
     * Properties
     */
    private static $uniqueInstance = NULL; // (tx_ptgsashop_orderAccessor object) Singleton unique instance
	
    
    /**
     * get unique instance
     * 
     * @return tx_ptgsapdfdocsrewrite_orderAccessor
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 26.11.2009
     */
	public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            $className = __CLASS__;
            self::$uniqueInstance = new $className;
        }
        return self::$uniqueInstance;
        
    }
	
    /**
     * Get uid / pid List from tx_ptgsashop_order_wrappers
     * 
     * @param $where string where constraint
     * @param $limit string limit
     * @return unknown_type
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 22.04.2010
     */
	public function loadOrderWrapperList($where = '', $limit = '') {
		// query preparation
        $select  = 'uid,
                    pid';

        $from    = 'tx_ptgsashop_order_wrappers';
        $groupBy = '';
        $orderBy = '';

        // exec query using TYPO3 DB API
        $a_rows = array();
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        trace(tx_pttools_div::returnLastBuiltSelectQuery($GLOBALS['TYPO3_DB'], $select, $from, $where, $groupBy, $orderBy, $limit));
        
        if ($res == false) {
            throw new tx_pttools_exception('Query failed', 1, $GLOBALS['TYPO3_DB']->sql_error());
        }

        while ($item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

            $wrapperList[$item['uid']] = $item;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $wrapperList;
	}
	
	public function isInvoiceCancled($relatedDocNo) {
		
		$select  = 'NUMMER';

        $from    = 'tx_ptgsaminidb_FSCHRIFT';
        $groupBy = '';
        $orderBy = '';
        $where = 'ALTAUFNR = "' . $relatedDocNo . '" and ERFART = "06ST"'; 
        $limit = '';
        
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        
        if($GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) == false) {
        	return false;
        }  else {
        	return true;
        }
	}
	
}
?>