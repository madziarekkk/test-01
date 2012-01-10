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
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Link_Resource extends Document_Resource {

    /**
     * Contains the valid database colums
     *
     * @var array
     */
    protected $validColumnsLink = array();

    /**
     * Get the valid database columns from database
     *
     * @return void
     */
    public function init() {

        // document
        parent::init();

        $this->validColumnsLink = $this->getValidTableColumns("documents_link");
    }

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {
        try {
            if ($id != null) {
                $this->model->setId($id);
            }

            $data = $this->db->fetchRow("SELECT * FROM documents LEFT JOIN documents_link ON documents.id = documents_link.id WHERE documents.id = ?", $this->model->getId());

            if ($data["id"] > 0) {
                $this->assignVariablesToModel($data);
                $this->model->getHref();
            }
            else {
                throw new Exception("Link with the ID " . $this->model->getId() . " doesn't exists");
            }

        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in the database
     *
     * @return void
     */
    public function create() {
        try {
            parent::create();

            $this->db->insert("documents_link", array(
                "id" => $this->model->getId()
            ));
        }
        catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Updates the data in the object to the database
     *
     * @return void
     */
    public function update() {
        try {
            $this->model->setModificationDate(time());
            $document = get_object_vars($this->model);

            foreach ($document as $key => $value) {

                // check if the getter exists
                $getter = "get" . ucfirst($key);
                if(!method_exists($this->model,$getter)) {
                    continue;
                }

                // get the value from the getter
                if(in_array($key, $this->validColumnsDocument) || in_array($key, $this->validColumnsLink)) {
                    $value = $this->model->$getter();
                } else {
                    continue;
                }

                if(is_bool($value)) {
                    $value = (int)$value;
                }
                if (in_array($key, $this->validColumnsDocument)) {
                    $dataDocument[$key] = $value;
                }
                if (in_array($key, $this->validColumnsLink)) {
                    $dataLink[$key] = $value;
                }
            }
            
            // first try to insert a new record, this is because of the recyclebin restore
            try {
                $this->db->insert("documents", $dataDocument);
            }
            catch (Exception $e) {
                $this->db->update("documents", $dataDocument, $this->db->quoteInto("id = ?", $this->model->getId()));
            }
            try {
                $this->db->insert("documents_link", $dataLink);
            }
            catch (Exception $e) {
                $this->db->update("documents_link", $dataLink, $this->db->quoteInto("id = ?", $this->model->getId()));
            }            
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes the object (and data) from database
     *
     * @return void
     */
    public function delete() {
        try {
            $this->db->delete("documents_link", $this->db->quoteInto("id = ?", $this->model->getId()));
            parent::delete();
        }
        catch (Exception $e) {
            throw $e;
        }
    }

}
