<?php

/**
 *  ocs-webserver
 *
 *  Copyright 2016 by pling GmbH.
 *
 *    This file is part of ocs-webserver.
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 **/
class Local_Auth_AdapterFactory
{

    const LOGIN_INFINITY = 'infinity';
    const LOGIN_HIVE = 'encryptionHive01';
    const LOGIN_PLING = 'encryptionPling01';
    const LOGIN_DEFAULT = 'default';
    const LOGIN_SSO = 'singleSingOn';

    /**
     * @param null $userIdentity
     * @param null $loginMethod
     *
     * @return Local_Auth_Adapter_Interface
     * @throws Zend_Auth_Adapter_Exception
     * @throws Zend_Exception
     */
    public static function getAuthAdapter($userIdentity = null, $credential = null, $loginMethod = null)
    {
        if (empty($loginMethod)) {
            $loginMethod = self::detectHashMethod($userIdentity, $credential);
        }

        return self::createAuthAdapter($loginMethod);
    }

    /**
     * @param $identity
     *
     * @return string
     */
    protected static function detectHashMethod($identity, $credential)
    {
        //$modelMember = new Application_Model_Member();
        //$memberData = $modelMember->findActiveMemberByIdentity($identity, $credential);
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($identity)) {
            $sql = "SELECT * FROM member AS m WHERE mail = :identity AND (`password` = :passHive OR `password` = :passOcs)";
        } else {
            $sql = "SELECT * FROM member AS m WHERE username = :identity AND (`password` = :passHive OR `password` = :passOcs)";
        }

        $memberData = Zend_Db_Table::getDefaultAdapter()->fetchRow($sql, array('identity' => $identity,
                                                                               'passHive' => Local_Auth_Adapter_Ocs::getEncryptedPassword($credential, Application_Model_DbTable_Member::PASSWORD_TYPE_HIVE),
                                                                               'passOcs' => Local_Auth_Adapter_Ocs::getEncryptedPassword($credential, Application_Model_DbTable_Member::PASSWORD_TYPE_OCS)
            )
        );

        if (count($memberData) == 0) {
            return self::LOGIN_DEFAULT;
        }

        if (Application_Model_Member::PASSWORD_TYPE_HIVE == $memberData['password_type']) {
            return self::LOGIN_HIVE;
        }
        //if ($modelMember->isHiveUser($memberData)) {
        //    return self::LOGIN_HIVE;
        //}

        return self::LOGIN_DEFAULT;
    }

    /**
     * @param $provider
     *
     * @return Local_Auth_Adapter_Ocs|Local_Auth_Adapter_RememberMe|Local_Auth_Adapter_SsoToken
     * @throws Zend_Auth_Adapter_Exception
     * @throws Zend_Exception
     */
    protected static function createAuthAdapter($provider)
    {
        switch ($provider) {
            case self::LOGIN_INFINITY:
                $authAdapter = new Local_Auth_Adapter_RememberMe(Zend_Registry::get('db'));
                break;

            case self::LOGIN_SSO:
                $authAdapter = new Local_Auth_Adapter_SsoToken(Zend_Registry::get('db'));
                break;

            case self::LOGIN_HIVE:
                $authAdapter = new Local_Auth_Adapter_Ocs(Zend_Registry::get('db'), 'member');
                $authAdapter->setEncryption(Local_Auth_Adapter_Ocs::SHA);
                break;

            case self::LOGIN_PLING:
            case self::LOGIN_DEFAULT:
            default:
                $authAdapter = new Local_Auth_Adapter_Ocs(Zend_Registry::get('db'), 'member');
                $authAdapter->setEncryption(Local_Auth_Adapter_Ocs::MD5);
                break;
        }

        return $authAdapter;
    }

}