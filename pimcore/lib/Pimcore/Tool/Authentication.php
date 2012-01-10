<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Authentication {


    /**
     * @static
     * @throws Exception
     * @return User
     */
    public static function authenticateSession () {

        // start session if necessary
        self::initSession();

        // get session namespace
        $adminSession = new Zend_Session_Namespace("pimcore_admin");

        $thawedUser = User::thaw($adminSession->frozenuser);
        if ($thawedUser instanceof User) {
            return $thawedUser;
        }
    }

    /**
     * @static
     * @throws Exception
     * @return User
     */
    public static function authenticateDigest () {

        // the following is a fix for Basic Auth in an FastCGI Environment
        if (isset($_SERVER['Authorization']) && !empty($_SERVER['Authorization'])) {
            $parts = explode(" ", $_SERVER['Authorization']);
            $type = array_shift($parts);
            $cred = implode(" ", $parts);

            if ($type == 'Digest') {
                $_SERVER["PHP_AUTH_DIGEST"] = $cred;
            }
        }

        // only digest auth is supported anymore
        try {

            $auth = new Sabre_HTTP_DigestAuth();
            $auth->setRealm("pimcore");
            $auth->init();

            if ($user = User::getByName($auth->getUsername())) {
                if(!$user->isAdmin()) {
                    throw new Exception("Only admins can access WebDAV");
                }
                if ($auth->validateA1($user->getPassword())) {
                    return $user;
                }
            }
            throw new Exception("Authentication required");
        }
        catch (Exception $e) {
            $auth->requireLogin();
            echo "Authentication required\n";
            die();
        }
    }


    /**
     * @static
     * @return void
     */
    public static function initSession() {

        Zend_Session::setOptions(array(
            "throw_startup_exceptions" => false,
            "gc_maxlifetime" => 7200,
            "name" => "pimcore_admin_sid",
            "strict" => false,
            "use_only_cookies" => false
        ));

        try {
            // register session
            $front = Zend_Controller_Front::getInstance();
            if ($front->getRequest() != null && $front->getRequest()->getParam("pimcore_admin_sid")) {
                // hack to get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                $_REQUEST["pimcore_admin_sid"] = $front->getRequest()->getParam("pimcore_admin_sid");
                $_COOKIE["pimcore_admin_sid"] = $front->getRequest()->getParam("pimcore_admin_sid");
            }
            if (!empty($_GET["pimcore_admin_sid"])) {
                // hack to get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                $_REQUEST["pimcore_admin_sid"] = $_GET["pimcore_admin_sid"];
                $_COOKIE["pimcore_admin_sid"] = $_GET["pimcore_admin_sid"];
            }

            try {
                if(!Zend_Session::isStarted()) {
                    Zend_Session::start();
                }
            }
            catch (Exception $e) {
                logger::error("Problem while starting session");
                logger::error($e);
            }

        }
        catch (Exception $e) {
            logger::emergency("there is a problem with admin session");
            die();
        }
    }


    /**
     * @static
     * @param  string $plainTextPassword
     * @return string
     */
    public static function getPasswordHash($username, $plainTextPassword) {
        return md5($username . ":pimcore:" . $plainTextPassword);
    }

    /**
     * @static
     * @param  string $username
     * @param  string $passwordHash
     * @param  string $algorithm
     * @param  string $mode
     * @return string
     */
    public static function generateToken($username, $passwordHash, $algorithm = MCRYPT_TRIPLEDES, $mode = MCRYPT_MODE_ECB) {

        $data = time() - 1 . '|' . $username;

        $key = $passwordHash;


        // append pkcs5 padding to the data
        $blocksize = mcrypt_get_block_size($algorithm, $mode);
        $pkcs = $blocksize - (strlen($data) % $blocksize);
        $data .= str_repeat(chr($pkcs), $pkcs);

        //encrypt
        $td = mcrypt_module_open($algorithm, '', $mode, '');

        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), null);
        $ks = mcrypt_enc_get_key_size($td);
        $key = substr($key, 0, $ks);
        mcrypt_generic_init($td, $key, $iv);
        $encrypted = mcrypt_generic($td, $data);
        $raw = base64_encode($encrypted);

        $token = "";
        for ($i = 0; $i < strlen($raw); $i++) {
            $token .= bin2hex($raw[$i]);
        }
        return $token;


    }

    /**
     * @static
     * @param  string $hex
     * @return  string
     */
    protected static function hex2str($hex) {
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        return $str;
    }


    /**
     * @static
     * @param  string $token
     * @param  string $algorithm
     * @param  string $mode
     * @return array
     */
    public static function decrypt($key, $token, $algorithm, $mode) {

        $encrypted = base64_decode(self::hex2str($token));


        $td = mcrypt_module_open($algorithm, '', $mode, '');

        //this takes up to 10 seconds ... WTF? Just use NULL ... ECB does not need an IV
        //$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), null);
        $iv = null;

        @mcrypt_generic_deinit($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $decrypted = str_replace(chr(8), "", $decrypted);
        return explode("|", $decrypted);
    }


    /**
     * @static
     * @throws Exception
     * @param  string $username
     * @param  string $token
     * @param bool $adminRequired
     * @return User
     */
    public static function tokenAuthentication($username, $token, $algorithm, $mode, $adminRequired = false) {


        $user = User::getByName($username);

            if (!$user instanceof User) {
                throw new Exception("invalid username");
            } else if (!$user->isActive()) {
                throw new Exception("user inactive");
            } else if ($adminRequired and !$user->isAdmin()) {
                throw new Exception("no permission");
            }

        $passwordHash = $user->getPassword();
        $decrypted = Pimcore_Tool_Authentication::decrypt($passwordHash, $token, $algorithm, $mode);

        $timestamp = $decrypted[0];
        $timeZone = date_default_timezone_get();
        date_default_timezone_set("UTC");

        if ($timestamp > time() or $timestamp < (time() - (60 * 30))) {
            throw new Exception("invalid timestamp");
        }
        date_default_timezone_set($timeZone);
        return $user;

    }
}