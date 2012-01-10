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

class Document_Tag_Block extends Document_Tag {

    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @var array
     */
    public $indices = array();

    /**
     * Current step of the block while iteration
     *
     * @var integer
     */
    public $current = 0;


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "block";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->indices;
    }

    /**
     * @see Document_Tag_Interface::admin
     */
    public function admin() {
        // nothing to do
    }

    /**
     * @see Document_Tag_Interface::frontend
     */
    public function frontend() {
        // nothing to do
        return null;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->indices = unserialize($data);
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->indices = $data;
    }

    /**
     * @return void
     */
    public function setDefault() {
        if (empty($this->indices) && $this->options["default"]) {
            for ($i = 0; $i < intval($this->options["default"]); $i++) {
                $this->indices[$i] = $i + 1;
            }
        }
    }

    /**
     * Loops through the block
     *
     * @return boolean
     */
    public function enumerate() {

        $this->setDefault();

        if ($this->current > 0) {
            $this->blockEnd();
        }
        else {
            $this->start();
        }

        if ($this->current < count($this->indices) && $this->current < $this->options["limit"]) {
            $this->blockStart();
            $this->current++;
            return true;
        }
        else {
            $this->end();
            return false;
        }
    }
    
    /**
     * Alias for enumerate
     *
     * @see enumerate()
     * @return boolean
     */
    public function loop() {
        return $this->enumerate();
    }

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return void
     */
    public function start() {

        $this->setupStaticEnvironment();
        
        // get configuration data for admin
        if (method_exists($this, "getDataEditmode")) {
            $data = $this->getDataEditmode();
        }
        else {
            $data = $this->getData();
        }

        $options = array(
            "options" => $this->getOptions(),
            "data" => $data,
            "name" => $this->getName(),
            "id" => "pimcore_editable_" . $this->getName(),
            "type" => $this->getType()
        );
        $options = @Zend_Json::encode($options);
        //$options = base64_encode($options);
        
        $this->outputEditmode('
            <script type="text/javascript">
                editableConfigurations.push('.$options.');
            </script>
        ');
        
        // set name suffix for the whole block element, this will be addet to all child elements of the block
        $suffixes = Zend_Registry::get("pimcore_tag_block_current");
        $suffixes[] = $this->getName();
        Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode('<div id="pimcore_editable_' . $this->getName() . '" name="' . $this->getName() . '" class="pimcore_editable pimcore_tag_' . $this->getType() . '" type="' . $this->getType() . '">');
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     *
     * @return void
     */
    public function end() {

        $this->current = 0;

        // remove the suffix which was set by self::start()
        $suffixes = Zend_Registry::get("pimcore_tag_block_current");
        array_pop($suffixes);
        Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode("</div>");
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     *
     * @return void
     */
    public function blockStart() {

        // set the current block suffix for the child elements (0, 1, 3, ...) | this will be removed in Pimcore_View_Helper_Tag::tag
        $suffixes = Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = $this->indices[$this->current];
        Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);

        $this->outputEditmode('<div class="pimcore_block_entry ' . $this->getName() . '" key="' . $this->indices[$this->current] . '">');
        $this->outputEditmode('<div class="pimcore_block_buttons">');
        $this->outputEditmode('<div class="pimcore_block_amount"></div>');
        $this->outputEditmode('<div class="pimcore_block_plus"></div>');
        $this->outputEditmode('<div class="pimcore_block_minus"></div>');
        $this->outputEditmode('<div class="pimcore_block_up"></div>');
        $this->outputEditmode('<div class="pimcore_block_down"></div>');
        $this->outputEditmode('<div class="pimcore_block_clear"></div>');
        $this->outputEditmode('</div>');
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     *
     * @return void
     */
    public function blockEnd() {

        $suffixes = Zend_Registry::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);

        $this->outputEditmode('</div>');
    }

    /**
     * Sends data to the output stream
     *
     * @param string $v
     * @return void
     */
    public function outputEditmode($v) {
        if ($this->getEditmode()) {
            echo $v . "\n";
        }
    }

    /**
     * Setup some settings that are needed for blocks
     *
     * @return void
     */
    public function setupStaticEnvironment() {

        // setup static environment for blocks
        try {
            $current = Zend_Registry::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = array();
            }
        }
        catch (Exception $e) {
            $current = array();
        }

        try {
            $numeration = Zend_Registry::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = array();
            }
        }
        catch (Exception $e) {
            $numeration = array();
        }

        Zend_Registry::set("pimcore_tag_block_numeration", $numeration);
        Zend_Registry::set("pimcore_tag_block_current", $current);

    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options) {

        if (empty($options["limit"])) {
            $options["limit"] = 1000000;
        }

        $this->options = $options;
    }

    /**
     * Return the amount of block elements
     *
     * @return integer
     */
    public function getCount() {
        return count($this->indices);
    }

    /**
     * Return current iteration step
     *
     * @return integer
     */
    public function getCurrent() {
        return $this->current-1;
    }
    
    /**
     * Return current index
     *
     * @return integer
     */
    public function getCurrentIndex () {
        return $this->indices[$this->getCurrent()];
    }

    /**
     * If object was serialized, set the counter back to 0
     *
     * @return void
     */
    public function __wakeup() {
        $this->current = 0;
    }
    
    /**
     * @return bool
     */
    public function isEmpty () {
        return !(bool) count($this->indices);
    }


     /**
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $data
     * @return void
     */
    public function getFromWebserviceImport($wsElement){
        $data = $wsElement->value;
        if(($data->indices === null or is_array($data->indices)) and ($data->current==null or is_numeric($data->current)) ){
            $this->indices = $data->indices;
            $this->current = $data->current;
        } else  {
            throw new Exception("cannot get  values from web service import - invalid data");
        }


    }
    
}
