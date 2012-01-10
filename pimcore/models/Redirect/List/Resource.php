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
 * @package    Redirect
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Redirect_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Staticroute elements
     *
     * @return array
     */
    public function load() {

        $redirectsData = $this->db->fetchAll("SELECT id FROM redirects" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $redirects = array();
        foreach ($redirectsData as $redirectData) {
            $redirects[] = Redirect::getById($redirectData["id"]);
        }

        $this->model->setRedirects($redirects);
        return $redirects;
    }

    public function getTotalCount() {

        try {
            $amount = $this->db->fetchRow("SELECT COUNT(*) as amount FROM redirects " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception $e) {

        }

        return $amount["amount"];
    }

}
