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

class Admin_SnippetController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->_getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->_getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->_getParam("id"), "document");

        $snippet = Document_Snippet::getById($this->_getParam("id"));
        $modificationDate = $snippet->getModificationDate();
        
        $snippet = $this->getLatestVersion($snippet);
        
        $snippet->getVersions();
        //$snippet->getPermissions();
        $snippet->getScheduledTasks();
        $snippet->getPermissionsForUser($this->getUser());
        $snippet->idPath = Pimcore_Tool::getIdPathForElement($snippet);

        $this->minimizeProperties($snippet);

        // unset useless data
        $snippet->setElements(null);

        if ($snippet->isAllowed("view")) {
            $this->_helper->json($snippet);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {
        if ($this->_getParam("id")) {
            $snippet = Document_Snippet::getById($this->_getParam("id"));
            $snippet = $this->getLatestVersion($snippet);
            
            $snippet->getPermissionsForUser($this->getUser());
            $snippet->setUserModification($this->getUser()->getId());

            // save to session
            $key = "document_" . $this->_getParam("id");
            $session = new Zend_Session_Namespace("pimcore_documents");
            $session->$key = $snippet;


            if ($this->_getParam("task") == "unpublish") {
                $snippet->setPublished(false);
            }
            if ($this->_getParam("task") == "publish") {
                $snippet->setPublished(true);
            }


            if (($this->_getParam("task") == "publish" && $snippet->isAllowed("publish")) or ($this->_getParam("task") == "unpublish" && $snippet->isAllowed("unpublish"))) {
                $this->setValuesToDocument($snippet);

                try {
                    $snippet->save();
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }


            }
            else {
                if ($snippet->isAllowed("save")) {
                    $this->setValuesToDocument($snippet);

                    try {
                        $snippet->saveVersion();
                        $this->_helper->json(array("success" => true));
                    } catch (Exception $e) {
                        $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                    }


                }
            }
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document $snippet) {

        $this->addSettingsToDocument($snippet);
        $this->addDataToDocument($snippet);
        $this->addSchedulerToDocument($snippet);
        $this->addPropertiesToDocument($snippet);
    }

}
