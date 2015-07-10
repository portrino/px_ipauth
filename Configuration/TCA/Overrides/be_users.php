<?php
$tempColumns = Array (
    'tx_pxipauth_ip_list' => Array (
        'exclude' => 1,
        'label' => 'LLL:EXT:px_ipauth/Resources/Private/Language/locallang_db.xlf:be_users.tx_pxipauth_ip_list',
        'config' => Array (
            'type' => 'input',
            'size' => '30',
        )
    ),
    'tx_pxipauth_mode' => Array (
        'exclude' => 1,
        'label' => 'LLL:EXT:px_ipauth/Resources/Private/Language/locallang_db.xlf:be_users.tx_pxipauth_mode',
        'config' => Array (
            'type' => 'select',
            'items' => array (
                array(' ', 0),
                array('LLL:EXT:px_ipauth/Resources/Private/Language/locallang_db.xlf:be_users.tx_pxipauth_mode.I.auto', 1),
                array('LLL:EXT:px_ipauth/Resources/Private/Language/locallang_db.xlf:be_users.tx_pxipauth_mode.I.auto_only', 2),
            ),
        )
    )
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users','tx_pxipauth_ip_list, tx_pxipauth_mode;;;;1-1-1');