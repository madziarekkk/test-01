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

class Object_Class_Data_Languagemultiselect extends Object_Class_Data_Multiselect {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "languagemultiselect";


    /**
     * @var bool
     */
    public $onlySystemLanguages = false;


    public function configureOptions () {

        $validLanguages = (array) Pimcore_Tool::getValidLanguages();

        $languages = Zend_Locale::getTranslationList('language');
        asort($languages);
        $options = array();

        foreach ($languages as $short => $translation) {

            if($this->getOnlySystemLanguages()) {
                if(!in_array($short, $validLanguages)) {
                    continue;
                }
            }

            if (strlen($short) == 2 or (strlen($short) == 5 and strpos($short, "_")==2) ) {

                $options[] = array(
                    "key" => $translation,
                    "value" => $short
                );
            }
        }

        $this->setOptions($options);
    }

    /**
     * @return bool
     */
    public function getOnlySystemLanguages () {
        return $this->onlySystemLanguages;
    }

    /**
     * @param bool $value
     */
    public function setOnlySystemLanguages ($value) {
        $this->onlySystemLanguages = (bool) $value;
    }



    /*public function __sleep () {
        //$this->configureOptions();

        return get_object_vars($this);
    }
    */

    public function __wakeup () {
        $this->configureOptions();
    }
}
