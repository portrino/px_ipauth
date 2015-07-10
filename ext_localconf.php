<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY,  'auth' /* sv type */,  '\Portrino\PxIpauth\Service\IpAuthenticationService' /* sv key */,
		array(

			'title' => 'Automatic BE login by IP',
			'description' => 'Login a backend user automatically if one is found with the right IP configured.',

			'subtype' => 'getUserBE, authUserBE',

			'available' => TRUE,
			'priority' => 60,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'className' => '\Portrino\PxIpauth\Service\IpAuthenticationService',
		)
	);

