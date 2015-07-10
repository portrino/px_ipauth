<?php
namespace Portrino\PxIpauth\Service;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 André Wuttig <wuttig@portrino.de>, portrino GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IpAuthenticationService
 *
 * @package Portrino\PxIpauth\Service
 */
class IpAuthenticationService extends \TYPO3\CMS\Sv\AbstractAuthenticationService     {

    /**
     * 100 / 101 Authenticated / Not authenticated -> in each case go on with additonal auth
     */
    const STATUS_AUTHENTICATION_SUCCESS_CONTINUE = 100;
    const STATUS_AUTHENTICATION_FAILURE_CONTINUE = 101;

    /**
     * 200 - authenticated and no more checking needed - useful for IP checking without password
     */
    const STATUS_AUTHENTICATION_SUCCESS_BREAK = 200;

    /**
     * FALSE - this service was the right one to authenticate the user but it failed
     */
    const STATUS_AUTHENTICATION_FAILURE_BREAK = 0;

    const LOGIN_MODE_AUTO = 1;
    const LOGIN_MODE_AUTO_ONLY = 2;

    /**
     * @var array
     */
    protected $extConf;

    public function init() {
        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['px_ipauth']);
        return parent::init();
    }


    /**
     * Find a user by IP ('REMOTE_ADDR')
     *
     * @return    mixed    user array or false
     */
    function getUser()    {
        $matchingUsers = array();
        $clientIP = $this->getClientIp();
            // get all users
        $dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            $this->db_user['table'],
            'tx_pxipauth_mode > 0 AND tx_pxipauth_ip_list <> ""' .
            $this->db_user['check_pid_clause'] .
            $this->db_user['enable_clause']
        );

        if ($dbres) {
            while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres))    {
                if(GeneralUtility::cmpIP($clientIP, $row['tx_pxipauth_ip_list']))     {
                    $matchingUsers[] = $row;
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($dbres);
        }
            // if only one user matches return this user
        if(count($matchingUsers) == 1) {
            return reset($matchingUsers);
        }

        if(count($matchingUsers) > 1) {
            // if there are more than one users which match the given IP or subnetmask try to find the best fitting
            // simple walk through the users
            foreach ($matchingUsers as $matchingUser) {
                $values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $matchingUser['tx_pxipauth_ip_list'], TRUE);
                foreach ($values as $value) {
                    if (ip2long($value) != FALSE && ip2long($value) === ip2long($clientIP)) {
                        return $matchingUser;
                    }
                }
            }
                // return the first of the matchin users if no one was found which fits best
            return reset($matchingUsers);
        }




        return FALSE;
    }
    
    
    /**
     * Authenticate a user
     * Return 200 if the IP is right. This means that no more checks are needed. Otherwise authentication may fail because we may don't have a password.
     *
     * @param    array     Data of user.
     * @return    boolean
     */    
    function authUser($user) {
        $ret = self::STATUS_AUTHENTICATION_SUCCESS_CONTINUE;
            // any auto option set?
        if ($user['tx_pxipauth_mode'] > 0) {
            $IPList = trim($user['tx_pxipauth_ip_list']);
            
                // auto IP login only
            if ($user['tx_pxipauth_mode'] == self::LOGIN_MODE_AUTO_ONLY) {
                    // we check always - also without an given IP
                $ret= \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP($this->getClientIp(), $IPList);
                $ret = $ret ? self::STATUS_AUTHENTICATION_SUCCESS_BREAK : self::STATUS_AUTHENTICATION_FAILURE_BREAK;
                
                // this option is checked with an given IP only
            } elseif($IPList) {
                $ret= \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP($this->getClientIp(), $IPList);
                $ret = $ret  ? self::STATUS_AUTHENTICATION_SUCCESS_BREAK : self::STATUS_AUTHENTICATION_SUCCESS_CONTINUE;
            }
        }

        // Checking the domain (lockToDomain)
        if ($ret && $user['lockToDomain'] && $user['lockToDomain'] != $this->authInfo['HTTP_HOST']) {
                // Lock domain didn't match, so error:
            if ($this->writeAttemptLog) {
                $this->writelog(255, 3, 3, 1, 'Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!', array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
                \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\', locked domain \'%s\' did not match \'%s\'!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']), 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
            }
            $ret = self::STATUS_AUTHENTICATION_FAILURE_BREAK;
        }

        return $ret;
    }

    /**
     * Return clients IP address.
     *
     * @return string
     */
    private function getClientIp() {
        if ((boolean)$this->extConf['x_forwarded_for'] && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $this->authInfo['REMOTE_ADDR'];
    }

    /**
     * fetch groups by ip
     *
     * @param    array     Data of user.
     * @param    array     Already known groups
     * @return    mixed     groups array
     */
    function getGroups($user, $knownGroups)    {
        
        $groupDataArr = array();
        
        if ($this->mode=='getGroupsBE') {
            
            # Get the BE groups here
            # needs to be implemented in t3lib_userauthgroup
        }
        
        return $groupDataArr;
    }
}