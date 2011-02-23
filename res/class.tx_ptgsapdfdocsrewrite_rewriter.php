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

require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_articleAccessor.php';
 
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_lib.php';  // GSA shop library class with static methods and config constants

require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php';

require_once t3lib_extMgm::extPath('pt_gsapdfdocs').'res/class.tx_ptgsapdfdocs_invoice.php';
require_once t3lib_extMgm::extPath('pt_gsapdfdocs').'res/class.tx_ptgsapdfdocs_manifest.php';
require_once t3lib_extMgm::extPath('pt_gsapdfdocs').'res/class.tx_ptgsapdfdocs_div.php';

// use the original hook to trigger the invoice generation
require_once t3lib_extMgm::extPath('pt_gsapdfdocs_rewrite').'res/class.tx_ptgsapdfdocsrewrite_orderAccessor.php';

require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_orderWrapper.php';

define('PATH_tslib',PATH_site.'typo3/sysext/cms/tslib/');
require_once t3lib_extMgm::extPath('pt_tools').'res/inc/faketsfe.inc.php';



class tx_ptgsapdfdocsrewrite_rewriter {

	/**
	 * @var bool
	 */
	protected $excludeCancled;
	
	/**
	 * if true, add a replacement info and use the actual date 
	 * @var bool
	 */
	protected $generateReplacements;
	
	/**
	 * @param $excludeCancled bool
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 22.04.2010
	 */
	public function setEcxludeCancled($excludeCancled) {
		$this->excludeCancled = $excludeCancled;
	}
	
	/**
	 * @param $generateReplacementInvoice bool
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 07.05.2010
	 */
	public function setGenerateReplacementInvoices($generateReplacementInvoice) {
		$this->generateReplacements = $generateReplacementInvoice;
	}
	
    /**
     * @return array with status info
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 26.11.2009
     */
	public function rewriteAll() {
		
		$uidList = tx_ptgsapdfdocsrewrite_orderAccessor::getInstance()->loadOrderWrapperList();
		
		return $this->rewriteInvoices($uidList);
	}
	
	/**
	 * Rewrite invoices by Range
	 * 
	 * @param $orderWrapperStartUID
	 * @param $orderWrapperEndUID
	 * @return unknown_type
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 22.04.2010
	 */
	public function rewriteInvoiceByRange($orderWrapperStartUID, $orderWrapperEndUID) {
	
		$where = 'uid >= ' . (int)$orderWrapperStartUID . ' and uid <= ' . (int)$orderWrapperEndUID;
		
		$uidList = tx_ptgsapdfdocsrewrite_orderAccessor::getInstance()->loadOrderWrapperList($where);
		
		return $this->rewriteInvoices($uidList);
	}
	
	/**
	 * 
	 * 
	 * @param $relatedDocNo
	 * @return unknown_type
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 22.04.2010
	 */
	public function rewriteInvoiceByRelatedDocNo($relatedDocNo) {
		
		$where = 'related_doc_no = "' . $GLOBALS['TYPO3_DB']->quoteStr($relatedDocNo) . '"';
		
		$uidList = tx_ptgsapdfdocsrewrite_orderAccessor::getInstance()->loadOrderWrapperList($where);
		
		return $this->rewriteInvoices($uidList);
	}
	
	/**
	 * Rewrite all invoices in given list
	 *  
	 * @param $uidList
	 * @return array statusList
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 22.04.2010
	 */
	protected function rewriteInvoices($uidList) {
		
		$info = array();
		
		foreach($uidList as $uid => $item) {

			$orderWrapper = $this->buildOrderWrapper($uid);
			
			if($this->excludeCancled && tx_ptgsapdfdocsrewrite_orderAccessor::getInstance()->isInvoiceCancled($orderWrapper->get_relatedDocNo())) {
				$tmpInfo['relatedErpDocNo'] = 'Skipped ' . $orderWrapper->get_relatedDocNo() . ' (cancled)';
				$info[] = $tmpInfo;
			} else {
				$info[] = $this->rewriteInvoice($orderWrapper);	
			}
		}
		
		return $info;
	}
	
	protected function buildOrderWrapper($uid) {
		
		$orderWrapper = new tx_ptgsashop_orderWrapper($uid);
		$orderWrapper->loadLastUserToFeCustomer();
		
		return $orderWrapper;
	}
	
	/**
	 * Rewrite a invoice by orderWrapperObject
	 * 
	 * @param $orderWrapperObj
	 * @return array statusInfo
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 07.05.2010
	 */
	protected function rewriteInvoice(tx_ptgsashop_orderWrapper $orderWrapperObj) {
		
		tx_pttools_assert::isInstanceOf($orderWrapperObj,'tx_ptgsashop_orderWrapper',array('message'=>'No valid orderWrapper Object'));
                        
        $relatedErpDocNo = $orderWrapperObj->get_relatedDocNo();
        $gsaUid = $orderWrapperObj->get_customerId();
        $conf = tx_pttools_div::getTS('config.pt_gsapdfdocs.');
        
        tx_pttools_assert::isArray($conf, array('message' => 'No configuration found at "config.pt_gsapdfdocs."'));
                
            if (substr($relatedErpDocNo, 0, 2) == 'RE') {

                // try {
                
                    $replace = array(
                        '###GSAUID###' => $gsaUid,
                        '###GSAUIDMOD10###' => $gsaUid % 10, 
                        '###GSAUIDMOD100###' => $gsaUid % 100,
                        '###GSAUIDMOD1000###' => $gsaUid % 1000,
                        '###DAY###' => strftime('%d'),
                        '###MONTH###' => strftime('%m'),
                        '###YEAR###' => strftime('%Y'),
                        '###RELATEDERPDOCNO###' => ereg_replace('[^a-zA-Z0-9._-]', '_', $relatedErpDocNo)
                    );
                    
                    $path = str_replace(array_keys($replace), array_values($replace), $conf['invoicePath']);
                    
                    t3lib_div::mkdir_deep(PATH_site, dirname($path));
                    
                    $additionalMarkers = $conf['additionalMarkers.']; // TODO: stdWrap
                    $additionalMarkers['relatedErpDocNo'] = $relatedErpDocNo;
					$additionalMarkers['originalTimeStamp'] = $orderWrapperObj->get_orderObj()->getDate();
                    
					if(!$this->generateReplacements) {
						$additionalMarkers['date'] = $additionalMarkers['originalTimeStamp'];
					}
					
                    $pdfInvoice = new tx_ptgsapdfdocs_invoice();
                    $pdfInvoice
                        ->set_customerObj($orderWrapperObj->get_feCustomerObj())
                        ->set_orderObj($orderWrapperObj->get_orderObj())
                        ->fillMarkerArray()
                        ->set_xmlSmartyTemplate($conf['xmlSmartyTemplate'])
                        ->set_languageFile($conf['languageFile'])
                        // ->set_languageKey($conf['languageKey'])
                        ->addMarkers($additionalMarkers)
                        ->createXml()
                        ->renderPdf($path);
                        
            } 
            
            $statusInfo['relatedErpDocNo'] = $relatedErpDocNo;
            $statusInfo['path'] = $path;
            
            return $statusInfo;
	}
		
}
?>