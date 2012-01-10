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

    class Search_Backend_Data_Id {

        /**
         * @var int
         */
        public $id;

        /**
         * @var string
         */
        public $type;


        /**
         * @param  Element_Interface $webResource
         * @return void
         */
        public function __construct($webResource){
            $this->id = $webResource->getId();
            if($webResource instanceof Document){
                $this->type = "document";
            } else if ($webResource instanceof Asset){
                $this->type = "asset";
            } else if ($webResource instanceof Object_Abstract){
                $this->type = "object";
            } else {
                $this->type = "unknown";
            }
        }

        /**
         * @return int
         */
        public function getId(){
            return $this->id;
        }

        /**
         * @return string
         */
        public function getType(){
            return $this->type;
        }

    }