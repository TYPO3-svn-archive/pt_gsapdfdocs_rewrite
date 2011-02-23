<?php

/*
 * PT Tools 
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; 
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_beSubmodule.php'; // abstract backend submodule parent class

require_once t3lib_extMgm::extPath('pt_gsapdfdocs_rewrite').'res/class.tx_ptgsapdfdocsrewrite_rewriter.php';

class tx_ptgsapdfdocs_rewrite extends tx_pttools_beSubmodule {
	
	protected $errorFields;		// Fields wich are not valid
	protected $smartyCompileDir; 
		
	/**
	 * initialize the module
	 * 
	 * (non-PHPdoc)
	 * @see res/abstract/tx_pttools_beSubmodule#init()
	 */
	public function init() {
	
        try {                       
            // set parent class properties
            $this->extKey = 'pt_gsapdfdocs_rewrite';
            $this->extPrefix = 'pt_gsapdfdocs_rewrite';  // extension prefix (for CSS classes, session keys etc.)
            $this->cssRelPath = '../res/css/submodules.css';  // path to the CSS file to use for this module (relative path from the module's index.php file)
               
            $this->conf = tx_pttools_div::returnExtConfArray($this->extKey, true);
           
            $this->smartyCompileDir = PATH_site.'typo3temp/smarty/templates_c';
            
            parent::init(); // calls tx_pttools_beSubmodule::init()
            
        } catch (tx_pttools_exception $excObj) {
            
            $excObj->handleException();
            die($excObj->__toString());
            
        }
    }
    
	/**
	 * Adds items to the ->MOD_MENU array
	 * 
	 * (non-PHPdoc)
	 * @see res/abstract/tx_pttools_beSubmodule#menuConfig()
	 */
    public function menuConfig() {
        
        $this->MOD_MENU = array(
            'jumpMenuFunction' => array(
                '1' => $this->ll('rewrite_all'),
            )
        );
        
        parent::menuConfig(true);
        
    }
    
    
/**
     * "Controller": Calls the appropriate action and returns the module's HTML content
     *
     * @param       void
     * @return      string      the module's HTML content
     * @global      $GLOBALS['LANG']
     * @author      Daniel Lienert <lienert@punkt.de>
     * @since       2009-10-13
     */
    public function moduleContent() {
        
       $moduleContent = '';
	   $stateList = array();
       
	   $rewriter = new tx_ptgsapdfdocsrewrite_rewriter();
	   $rewriter->setEcxludeCancled(t3lib_div::GPvar('optExcludeCancledInvoices'));
	   $rewriter->setGenerateReplacementInvoices(t3lib_div::GPvar('optGenerateReplacementInvoices'));
	   
        try { 
            switch (true) {
                case t3lib_div::GPvar('cmdRewriteAll'): 
                 	
                 	$stateList = $rewriter->rewriteAll();   
                break;
                
                case t3lib_div::GPvar('cmdRewriteByRange'):
                 	$stateList = $rewriter->rewriteInvoiceByRange(t3lib_div::GPvar('rangeStart'), t3lib_div::GPvar('rangeEnd'));   
                break;
                
                case t3lib_div::GPvar('cmdRewriteByDocNo'):
                 	$stateList = $rewriter->rewriteInvoiceByRelatedDocNo(t3lib_div::GPvar('relatedDocNo'));
                break; 
                
                case t3lib_div::GPvar('cmdDownloadInvoice'):
                	
                break;
                
                default:
                    // execute jump menu related actions
                    if ($this->MOD_SETTINGS['jumpMenuFunction'] == '2') {
                 
                    }

                    // default action if no jump menu related actions to perform
					
            } 
            
            
        } catch (tx_pttools_exception $excObj) {
            $excObj->handleException();
            $moduleContent = '<i>'.$excObj->__toString().'</i>';
        }
        
        
        
        $moduleContent .= $this->showImportStatus($stateList);
        
        $moduleContent .= '<form name="rewriteByRangeForm" method="post" action="/typo3conf/ext/pt_gsapdfdocs_rewrite/mod1/index.php">';
        $moduleContent .= $this->showOptions();
        $moduleContent .= $this->showRewriteByDocNo();
        $moduleContent .= $this->showRewriteRange();
        $moduleContent .= $this->showRwriteAll();
        $moduleContent .= '</form>';
        
        return $moduleContent;   
    }
    
    protected function showImportStatus($stateList) {
    	$smarty = tx_smarty::smarty(array('compile_dir' => $this->smartyCompileDir));  
        $smartyTemplate = 'EXT:pt_gsapdfdocs_rewrite/res/smarty_tpl/stateList.html';
    	
        $smarty->assign('stateList', $stateList);
        $smarty->assign('totalCount', count($stateList));
        
        $content =  $smarty->fetch('file:'.t3lib_div::getFileAbsFileName($smartyTemplate));
        
    	return $this->doc->section($this->ll('rewriteStateHeader'), $content, 0, 1); 
    }
    
    
    
    /**
     * show the quick export section
     * 
     * @return unknown_type
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 13.10.2009
     */
    protected function showRwriteAll() {
    	
    	$content = '<input name="cmdRewriteAll" value="Rewrite all" id="cmdRewrite" type="submit">';
    	
    	return $this->doc->section($this->ll('invoiceRewriteAll'), $content, 0, 1); 
    }
    
    protected function showRewriteRange() {
    	
    	$smarty = tx_smarty::smarty(array('compile_dir' => $this->smartyCompileDir));  
        $smartyTemplate = 'EXT:pt_gsapdfdocs_rewrite/res/smarty_tpl/rewriteRangeForm.html';
    	
    	$content = $smarty->fetch('file:'.t3lib_div::getFileAbsFileName($smartyTemplate));
    	return $this->doc->section($this->ll('rewriteRangeHeader'), $content, 0, 1); 
    }
    
    
 	protected function showRewriteByDocNo() {
    	
    	$smarty = tx_smarty::smarty(array('compile_dir' => $this->smartyCompileDir));  
        $smartyTemplate = 'EXT:pt_gsapdfdocs_rewrite/res/smarty_tpl/rewriteDocNoForm.html';
    	
    	$content = $smarty->fetch('file:'.t3lib_div::getFileAbsFileName($smartyTemplate));
    	return $this->doc->section($this->ll('rewriteByDocNoHeader'), $content, 0, 1); 
    }
    
	protected function showOptions() {
    	
    	$smarty = tx_smarty::smarty(array('compile_dir' => $this->smartyCompileDir));  
        $smartyTemplate = 'EXT:pt_gsapdfdocs_rewrite/res/smarty_tpl/optionsForm.html';
    	
        if(t3lib_div::GPvar('optExcludeCancledInvoices')) {
        	$smarty->assign('optExcludeCancledInvoicesChecked', 'checked="checked"');	
        }
        
    	$content = $smarty->fetch('file:'.t3lib_div::getFileAbsFileName($smartyTemplate));
    	return $this->doc->section($this->ll('optionsHeader'), $content, 0, 1); 
    }
    
    protected function setErrorField($fieldName) {
    	$this->errorFields[$fieldName]['css'] = 'border:1px solid red; background-color: #ffcaca;';
    }
}
?>