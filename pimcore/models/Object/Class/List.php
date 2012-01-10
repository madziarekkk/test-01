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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_List extends Pimcore_Model_List_Abstract {

    /**
     * Contains the results of the list. They are all an instance of Object_Class
     *
     * @var array
     */
    public $classes;


    /**
     * Contains the results of the list. They are all an instance of Property_Predefined
     *
     * @todo remove dummy
     * @var array
     */
    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * @return array
     */
    function getClasses() {
        return $this->classes;
    }

    /**
     * @param array $properties
     * @return void
     */
    function setClasses($classes) {
        $this->classes = $classes;
    }
}
