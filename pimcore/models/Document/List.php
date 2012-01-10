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

class Document_List extends Pimcore_Model_List_Abstract implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

    /**
     * Return all documents as Type Document. eg. for trees an so on there isn't the whole data required
     *
     * @var boolean
     */
    public $objectTypeDocument = false;

    /**
     * Contains the results of the list
     *
     * @var array
     */
    public $documents = null;
    
    /**
     * @var boolean
     */
    public $unpublished = false;
    
    /**
     * Valid order keys
     *
     * @var array
     */
    public $validOrderKeys = array(
        "creationDate",
        "modificationDate",
        "id",
        "key",
        "index"
    );

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key) {
        return true;
        /*if (in_array($key, $this->validOrderKeys)) {
            return true;
        }
        return false;*/
    }

    /**
     * @return array
     */
    public function getDocuments() {
        if (!$this->documents === null) {
            $this->load();
        }
        return $this->documents;
    }

    /**
     * @param array $documents
     * @return void
     */
    public function setDocuments($documents) {
        $this->documents = $documents;
    }
    
    /**
     * @return bool
     */
    public function getUnpublished() {
        return $this->unpublished;
    }
    
    /**
     * @return bool
     */
    public function setUnpublished($unpublished) {
        $this->unpublished = (bool) $unpublished;
    }
    
    /**
     *
     * Methods for Zend_Paginator_Adapter_Interface
     */

    public function count() {
        return parent::getTotalCount();
    }

    public function getItems($offset, $itemCountPerPage) {
        parent::setOffset($offset);
        parent::setLimit($itemCountPerPage);
        return parent::load();
    }

    public function getPaginatorAdapter() {
        return $this;
    }
    

    /**
     * Methods for Iterator
     */

    public function rewind() {
        $this->getDocuments();
        reset($this->documents);
    }

    public function current() {
        $this->getDocuments();
        $var = current($this->documents);
        return $var;
    }

    public function key() {
        $this->getDocuments();
        $var = key($this->documents);
        return $var;
    }

    public function next() {
        $this->getDocuments();
        $var = next($this->documents);
        return $var;
    }

    public function valid() {
        $this->getDocuments();
        $var = $this->current() !== false;
        return $var;
    }
}
