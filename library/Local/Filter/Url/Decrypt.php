<?php
/**
 *  ocs-apiserver
 *
 *  Copyright 2016 by pling GmbH.
 *
 *    This file is part of ocs-apiserver.
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

class Local_Filter_Url_Decrypt implements Zend_Filter_Interface
{

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        return $this->decryptUrl($value);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function decryptUrl($url)
    {
        if (empty($url)) {
            return '';
        }

        /*  Set various encryption options. */
        $options = array(
            // Encryption type - Openssl or Mcrypt
            'adapter' => 'mcrypt',
            // Initialization vector
            'vector' => '236587hgtyujkirtfgty5678',
            // Encryption algorithm
            'algorithm' => 'rijndael-192',
            // Encryption key
            'key' => 'KFJGKDK$$##^FFS345678FG2'
        );

        /* Initialize the library and pass the options */
        $filter = new Zend_Filter_Decrypt($options);

        return rtrim($filter->filter($this->base64url_decode($url)));
    }

    protected function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

}