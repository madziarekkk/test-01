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


include_once("simple_html_dom.php");

class Pimcore_Tool_Less {


    public static function processHtml ($body) {
        $html = str_get_html($body);

        if(!$html) {
            return $body;
        }

        $styles = $html->find("link[rel=stylesheet/less]");

        $stylesheetContents = array();
        $processedPaths = array();

        foreach ($styles as $style) {

            $media = $style->media;
            if(!$media) {
                $media = "all";
            }

            $source = $style->href;
            $path = "";
            if (is_file(PIMCORE_ASSET_DIRECTORY . $source)) {
                $path = PIMCORE_ASSET_DIRECTORY . $source;
            }
            else if (is_file(PIMCORE_DOCUMENT_ROOT . $source)) {
                $path = PIMCORE_DOCUMENT_ROOT . $source;
            }

            // add the same file only one time
            if(in_array($path, $processedPaths)) {
                continue;
            }

            $compiledContent = "";

            if (is_file("file://".$path)) {

                $compiledContent = self::compile($path);

                // correct references inside the css
                $compiledContent = self::correctReferences($source, $compiledContent);

                $stylesheetContents[$media] .= $compiledContent . "\n";
                $style->outertext = "";

                $processedPaths[] = $path;
            }
        }

        // put compiled contents into single files, grouped by their media type
        if(count($stylesheetContents) > 0) {
            $head = $html->find("head",0);
            foreach ($stylesheetContents as $media => $content) {
                $stylesheetPath = PIMCORE_TEMPORARY_DIRECTORY."/less_".md5($content).".css";

                if(!is_file($stylesheetPath)) {
                    file_put_contents($stylesheetPath, $content);
                    chmod($stylesheetPath, 0766);
                }

                $head->innertext = $head->innertext . "\n" . '<link rel="stylesheet" media="' . $media . '" type="text/css" href="' . str_replace(PIMCORE_DOCUMENT_ROOT,"",$stylesheetPath) . '" />'."\n";
            }
        }

        $body = $html->save();

        return $body;
    }

    public static function compile ($path) {

        $conf = Pimcore_Config::getSystemConfig();

        // use the original less compiler if configured
        if($conf->outputfilters->lesscpath) {
            $output = array();
            exec($conf->outputfilters->lesscpath . " " . $path, $output);
            $compiledContent = implode($output);

            // add a comment to the css so that we know it's compiled by lessc
            $compiledContent = "\n\n/**** compiled with lessc (node.js) ****/\n\n" . $compiledContent;
        }

        // use php implementation of lessc if it doesn't work
        if(empty($compiledContent)) {
            $less = new lessc();
            $less->importDir = dirname($path);
            $compiledContent = $less->parse(file_get_contents($path));

            // add a comment to the css so that we know it's compiled by lessphp
            $compiledContent = "\n\n/**** compiled with lessphp ****/\n\n" . $compiledContent;
        }

        return $compiledContent;
    }


    protected static function correctReferences ($base, $content) {
        // check for url references
        preg_match_all("/url\((.*)\)/iU", $content, $matches);
        foreach ($matches[1] as $ref) {

            // do some corrections
            $ref = str_replace('"',"",$ref);
            $ref = str_replace(' ',"",$ref);
            $ref = str_replace("'","",$ref);

            $path = self::correctUrl($ref, $base);

            //echo $ref . " - " . $path . " - " . $url . "<br />";

            $content = str_replace($ref,$path,$content);
        }

        return $content;
    }


    protected static function correctUrl ($rel, $base) {
        /* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

        /* queries and anchors */
        if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

        /* parse base URL and convert to local variables:
           $scheme, $host, $path */
        extract(parse_url($base));

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ($rel[0] == '/') $path = '';

        /* dirty absolute URL */
        $abs = "$path/$rel";

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

        /* absolute URL is ready! */
        return $abs;
    }
}
