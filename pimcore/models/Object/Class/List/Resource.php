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

class Object_Class_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of object-classes for the specicifies parameters, returns an array of Object_Class elements
     *
     * @return array
     */
    public function load() {

        $classes = array();

        $classesRaw = $this->db->fetchAll("SELECT id FROM classes" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($classesRaw as $classRaw) {
            $classes[] = Object_Class::getById($classRaw["id"]);

        }

        $this->model->setClasses($classes);

        return $classes;
    }
}
