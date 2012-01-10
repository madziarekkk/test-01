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

require_once 'Zend/Controller/Plugin/Abstract.php';

class Pimcore_Controller_Plugin_CDN extends Zend_Controller_Plugin_Abstract {

    protected $enabled = true;
    protected $hostnames;
    protected $patterns;
    protected $cachedItems;
    protected $conf;
    
    const cacheKey = "cdn_pathes";

    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $conf = Pimcore_Config::getSystemConfig();
        if (!$conf->outputfilters) {
            return $this->disable();
        }

        if (!$conf->outputfilters->cdn || !$conf->outputfilters->cdnhostnames || !$conf->outputfilters->cdnpatterns) {
            return $this->disable();
        }
        
        $this->conf = $conf;
    }

    public function disable() {
        $this->enabled = false;
        return true;
    }
    
    protected function getHostnames () {
        if($this->hostnames === null) {
            $this->hostnames = array();
            $hosts = explode(",",(string) $this->conf->outputfilters->cdnhostnames);
            if(is_array($hosts) && count($hosts) > 0) {
                $this->hostnames = $hosts;
            }
        }
        return $this->hostnames;
    }
    
    protected function getPatterns () {
        if($this->patterns === null) {
            $this->patterns = array();
            $patterns = explode(",",$this->conf->outputfilters->cdnpatterns);
            if(is_array($patterns) && count($patterns) > 0) {
                $this->patterns = $patterns;
            }
        }
        return $this->patterns;
    }
    
    protected function pathMatch ($path) {
        foreach ($this->getPatterns() as $pattern) {
            if(@preg_match($pattern,$path)) {
                if(strpos($path,"/") === 0) {
                    return true;
                }
                return true;
            }
        }
        return false;
    }
    
    protected function getStorage () {
        if($this->cachedItems === null) {
            $this->cachedItems = array();
            if ($items = Pimcore_Model_Cache::load(self::cacheKey)) {
                $this->cachedItems = $items; 
            }
        }
        return $this->cachedItems;
    }
    
    protected function rewritePath ($path) {
        $store = $this->getStorage();
        if($store[$path]) {
            return $store[$path];
        }
        
        $hosts = $this->getHostnames();
        $i = array_rand($hosts);
        
        $new = $hosts[$i].$path;
        $this->cachedItems[$path] = $new;
        
        return $new;
    }

    public function dispatchLoopShutdown() {
        
        if(!Pimcore_Tool::isHtmlResponse($this->getResponse())) {
            return;
        }
        
        if ($this->enabled) {
            
            include_once("simple_html_dom.php");
            
            $body = $this->getResponse()->getBody();
            
            $html = str_get_html($body);
            if($html) {
                $elements = $html->find("link[rel=stylesheet], img, script[src]");

                foreach ($elements as $element) {
                    if($element->tag == "link") {
                        if($this->pathMatch($element->href)) {
                            $element->href = $this->rewritePath($element->href);
                        }
                    }
                    else if ($element->tag == "img") {
                        if($this->pathMatch($element->src)) {
                            $element->src = $this->rewritePath($element->src);
                        }
                    }
                    else if ($element->tag == "script") {
                        if($this->pathMatch($element->src)) {
                            $element->src = $this->rewritePath($element->src);
                        }
                    }
                }

                $body = $html->save();
                $this->getResponse()->setBody($body);

                // save storage
                Pimcore_Model_Cache::save($this->cachedItems, self::cacheKey, array(), 3600);
            }
        }
    }
}

