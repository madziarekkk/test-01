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

class Pimcore_View_Helper_PimcoreNavigation extends Zend_View_Helper_Abstract
{

    public static $_controller;

    public static function getController()
    {
        if (!self::$_controller) {
            self::$_controller = new Pimcore_View_Helper_PimcoreNavigation_Controller();
        }

        return self::$_controller;
    }

    public function pimcoreNavigation()
    {
        return self::getController();
    }


}


class Pimcore_View_Helper_PimcoreNavigation_Controller
{

    protected $_activeDocument;

    protected $_navigationContainer;

    protected $_htmlMenuIdPrefix;

    public function getNavigation($activeDocument,$navigationRootDocument = null, $htmlMenuIdPrefix = null)
    {

        $this->_activeDocument = $activeDocument;
        $this->_htmlMenuIdPrefix = $htmlMenuIdPrefix;

        $this->_navigationContainer = new Zend_Navigation();

        if (!$navigationRootDocument) {
            $navigationRootDocument = Document::getById(1);
        }

        if ($navigationRootDocument->hasChilds()) {
            $this->buildNextLevel($navigationRootDocument, null,true);
        }
        return $this->_navigationContainer;
    }

    /**
     * @param  Document $parentDocument
     * @param  Pimcore_Navigation_Page_Uri $parentPage
     * @return void
     */
    protected function buildNextLevel($parentDocument, $parentPage = null, $isRoot=false)
    {

        $pages = array();

        $childs = $parentDocument->getChilds();
        if (is_array($childs)) {
            foreach ($childs as $child) {

                if (($child instanceof Document_Page or $child instanceof Document_Link or $child instanceof Document_Hardlink ) and $child->getProperty("navigation_name") and !$child->getProperty("navigation_exclude")) {

                    $active = false;

                    if(strpos($this->_activeDocument->getFullPath(), $child->getFullPath()."/")===0 or $this->_activeDocument->getFullPath()== $child->getFullPath()){
                                           $active=true;
                    }

                    $path = $child->getFullPath();
                    if($child instanceof Document_Link){
                        $path = $child->getHref();
                    }

                    $page = new Pimcore_Navigation_Page_Uri();
                    $page->setUri($path.$child->getProperty("navigation_parameters").$child->getProperty("navigation_anchor"));
                    $page->setLabel($child->getProperty("navigation_name"));
                    $page->setActive($active);
                    $page->setId($this->_htmlMenuIdPrefix.$child->getId());
                    $page->setClass($child->getProperty("navigation_class"));
                    $page->setTarget($child->getProperty("navigation_target"));
                    $page->setTitle($child->getProperty("navigation_title"));
                    $page->setAccesskey($child->getProperty("navigation_accesskey"));
                    $page->setTabindex($child->getProperty("navigation_tabindex"));
                    $page->setRelation($child->getProperty("navigation_relation"));
                    $page->setDocument($child);

                    if($active and !$isRoot){
                        $page->setClass($page->getClass()." active");
                    } else if($active and $isRoot){
                        $page->setClass($page->getClass()." main mainactive");
                    } else if ($isRoot){
                        $page->setClass($page->getClass()." main");
                    }

                    if ($child->hasChilds()) {
                        $childPages = $this->buildNextLevel($child, $page,false);
                        $page->setPages($childPages);
                    }


                    $pages[] = $page;

                    if($isRoot){
                        $this->_navigationContainer->addPage($page);
                    }

                }

            }
        }
        return $pages;

    }

}