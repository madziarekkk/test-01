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

class Install_IndexController extends Pimcore_Controller_Action {


    public function init() {
        parent::init();

        $maxExecutionTime = 300;
        @ini_set("max_execution_time", $maxExecutionTime);
        set_time_limit($maxExecutionTime);

        Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

        if (is_file(PIMCORE_CONFIGURATION_SYSTEM)) {
            $this->_redirect("/admin");
        }
    }

    public function indexAction() {

        $errors = array();

        // check permissions
        $files = rscandir(PIMCORE_WEBSITE_PATH . "/var/");

        foreach ($files as $file) {
            if (is_dir($file) && !is_writable($file)) {
                $errors[] = "Please ensure that the whole /" . PIMCORE_FRONTEND_MODULE . "/var folder is writeable (recursivly)";
                break;
            }
        }

        $this->view->errors = $errors;
    }

    public function installAction() {

        // try to establish a mysql connection
        try {

            $db = Zend_Db::factory($this->_getParam("mysql_adapter"),array(
                'host' => $this->_getParam("mysql_host"),
                'username' => $this->_getParam("mysql_username"),
                'password' => $this->_getParam("mysql_password"),
                'dbname' => $this->_getParam("mysql_database"),
                "port" => $this->_getParam("mysql_port")
            ));

            $db->getConnection();

            // check utf-8 encoding
            $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
            if ($result['Value'] != "utf8") {
                $errors[] = "Database charset is not utf-8";
            }
        }
        catch (Exception $e) {
            $errors[] = "Couldn't establish connection to mysql: " . $e->getMessage();
        }

        // check username & password
        if (strlen($this->_getParam("admin_password")) < 4 || strlen($this->_getParam("admin_username")) < 4) {
            $errors[] = "Username and password should have at least 4 characters";
        }

        if (empty($errors)) {

            // write configuration file
            $settings = array(
                "general" => array(
                    "timezone" => "Europe/Berlin",
                    "language" => "en",
                    "validLanguages" => "en",
                    "debug" => "1",
                    "theme" => "/pimcore/static/js/lib/ext/resources/css/xtheme-blue.css",
                    "loginscreenimageservice" => "1",
                    "welcomescreen" => "1",
                    "loglevel" => array(
                        "debug" => "1",
                        "info" => "1",
                        "notice" => "1",
                        "warning" => "1",
                        "error" => "1",
                        "critical" => "1",
                        "alert" => "1",
                        "emergency" => "1"
                    )
                ),
                "database" => array(
                    "adapter" => $this->_getParam("mysql_adapter"),
                    "params" => array(
                        "host" => $this->_getParam("mysql_host"),
                        "username" => $this->_getParam("mysql_username"),
                        "password" => $this->_getParam("mysql_password"),
                        "dbname" => $this->_getParam("mysql_database"),
                        "port" => $this->_getParam("mysql_port"),
                    )
                ),
                "documents" => array(
                    "versions" => array(
                        "steps" => "10"
                    ),
                    "default_controller" => "default",
                    "default_action" => "default",
                    "error_page" => "/",
                    "allowtrailingslash" => "no",
                    "allowcapitals" => "no"
                ),
                "objects" => array(
                    "versions" => array(
                        "steps" => "10"
                    )
                ),
                "assets" => array(
                    "versions" => array(
                        "steps" => "10"
                    )
                ),
                "services" => array(),
                "cache" => array(
                    "excludeCookie" => "pimcore_admin_sid"
                ),
                "httpclient" => array(
                    "adapter" => "Zend_Http_Client_Adapter_Socket"
                )
            );

            $config = new Zend_Config($settings, true);
            $writer = new Zend_Config_Writer_Xml(array(
                "config" => $config,
                "filename" => PIMCORE_CONFIGURATION_SYSTEM
            ));
            $writer->write();


            // insert db dump
            $db = Pimcore_Resource::get();
            $mysqlInstallScript = file_get_contents(PIMCORE_PATH . "/modules/install/mysql/install.sql");

            // remove comments in SQL script
            $mysqlInstallScript = preg_replace("/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/","",$mysqlInstallScript);

            // get every command as single part
            $mysqlInstallScripts = explode(";",$mysqlInstallScript);

            // execute every script with a separate call, otherwise this will end in a PDO_Exception "unbufferd queries, ..." seems to be a PDO bug after some googling
            foreach ($mysqlInstallScripts as $m) {
                $sql = trim($m);
                if(strlen($sql) > 0) {
                    $sql .= ";";
                    $db->query($m);
                }
            }

            // get a new database connection
            $db = Pimcore_Resource::reset();

            // insert data into database
            $db->insert("assets", array(
                "id" => 1,
                "parentId" => 0,
                "type" => "folder",
                "filename" => "",
                "path" => "/",
                "creationDate" => time(),
                "modificationDate" => time(),
                "userOwner" => 1,
                "userModification" => 1
            ));
            $db->insert("documents", array(
                "id" => 1,
                "parentId" => 0,
                "type" => "page",
                "key" => "",
                "path" => "/",
                "index" => 999999,
                "published" => 1,
                "creationDate" => time(),
                "modificationDate" => time(),
                "userOwner" => 1,
                "userModification" => 1
            ));
            $db->insert("documents_page", array(
                "id" => 1,
                "controller" => "",
                "action" => "",
                "template" => "",
                "title" => "",
                "description" => "",
                "keywords" => ""
            ));
            $db->insert("objects", array(
                "o_id" => 1,
                "o_parentId" => 0,
                "o_type" => "folder",
                "o_key" => "",
                "o_path" => "/",
                "o_index" => 999999,
                "o_published" => 1,
                "o_creationDate" => time(),
                "o_modificationDate" => time(),
                "o_userOwner" => 1,
                "o_userModification" => 1
            ));

            $userPermissions = array(
                array(
                    "key" =>            "assets",
                    "translation" =>    "permission_assets"
                ),
                array(
                    "key" =>            "classes",
                    "translation" =>    "permission_classes"
                ),
                array(
                    "key" =>            "clear_cache",
                    "translation" =>    "permission_clear_cache"
                ),
                array(
                    "key" =>            "clear_temp_files",
                    "translation" =>    "permission_clear_temp_files"
                ),
                array(
                    "key" =>            "document_types",
                    "translation" =>    "permission_document_types"
                ),
                array(
                    "key" =>            "documents",
                    "translation" =>    "permission_documents"
                ),
                array(
                    "key" =>            "objects",
                    "translation" =>    "permission_objects"
                ),
                array(
                    "key" =>            "plugins",
                    "translation" =>    "permission_plugins"
                ),
                array(
                    "key" =>            "predefined_properties",
                    "translation" =>    "permission_predefined_properties"
                ),
                array(
                    "key" =>            "routes",
                    "translation" =>    "permission_routes"
                ),
                array(
                    "key" =>            "seemode",
                    "translation" =>    "permission_seemode"
                ),
                array(
                    "key" =>            "system_settings",
                    "translation" =>    "permission_system_settings"
                ),
                array(
                    "key" =>            "thumbnails",
                    "translation" =>    "permission_thumbnails"
                ),
                array(
                    "key" =>            "translations",
                    "translation" =>    "permission_translations"
                ),
                array(
                    "key" =>            "users",
                    "translation" =>    "permission_users"
                ),
                array(
                    "key" =>            "update",
                    "translation" =>    "permissions_update"
                ),
                array(
                    "key" =>            "redirects",
                    "translation" =>    "permissions_redirects"
                ),array(
                    "key" =>            "glossary",
                    "translation" =>    "permissions_glossary"
                ),
                array(
                    "key" =>            "reports",
                    "translation" =>    "permissions_reports_marketing"
                )
            );
            foreach ($userPermissions as $up) {
                $db->insert("users_permission_definitions", $up);
            }

            Pimcore::initConfiguration();


            $user = User::create(array(
                "parentId" => 0,
                "username" => $this->_getParam("admin_username"),
                "password" => Pimcore_Tool_Authentication::getPasswordHash($this->_getParam("admin_username"),$this->_getParam("admin_password")),
                "hasCredentials" => true,
                "active" => true
            ));
            $user->setAdmin(true);
            $user->save();

            $this->_helper->json(array(
                "success" => true
            ));
        }

        else {
            echo implode("<br />", $errors);
            die();
        }

    }
} 
