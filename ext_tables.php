<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModulePath('txptgsaadminM1_txptgsapdfdocsrewriteM1', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
		
	t3lib_extMgm::addModule('txptgsaadminM1', 'txptgsapdfdocsrewriteM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}
?>