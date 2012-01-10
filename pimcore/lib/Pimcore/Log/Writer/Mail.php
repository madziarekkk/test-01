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


class Pimcore_Log_Writer_Mail extends Zend_Log_Writer_Mail{

    /**
     * @var string
     */
    protected $_tempfile;
    /**
     * @var Zend_Log
     */
    protected  $_tempLogger;

    /**
     * Class constructor.
     *
     * Constructs the pimcore mail writer; requires a Zend_Mail instance, and takes an
     * optional Zend_Layout instance.  If Zend_Layout is being used,
     * $this->_layout->events will be set for use in the layout template.
     *
     * @param string
     * @param  Zend_Mail $mail Mail instance
     * @param  Zend_Layout $layout Layout instance; optional
     * @return void
     */
    public function __construct($tempfile, Zend_Mail $mail, Zend_Layout $layout = null)
    {
         $this->_tempfile = $tempfile;
         $writerFile = new Zend_Log_Writer_Stream($tempfile);
         $this->_tempLogger = new Zend_Log($writerFile);
         parent::__construct($mail,$layout);


    }

    /**
     * calls prent _write and and writes temp log file
     *
     * @param  array $event Event data
     * @return void
     */
    protected function _write($event)
    {

        if(!is_file($this->_tempfile)){
            $fh = @fopen($this->_tempfile,'a');
            @fwrite($fh, "... continued ...\r\n");
            @fclose($fh);
            $writerFile = new Zend_Log_Writer_Stream($this->_tempfile);
            $this->_tempLogger = new Zend_Log($writerFile);
        }
        $this->_tempLogger->log($event['message'],$event['priority']);
        parent::_write($event);
    }

    /**
     * Sends mail to recipient(s) if log entries are present.  Note that both
     * plaintext and HTML portions of email are handled here.
     *
     * @return void
     */
    public function shutdown()
    {
        parent::shutdown();
        unset($this->_tempLogger);
        if(is_file($this->_tempfile)){
            unlink($this->_tempfile);
        }
        

    }


}
 
