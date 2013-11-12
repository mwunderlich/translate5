<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

class Editor_ReferencefileController extends editor_Controllers_EditorrestController {

    protected $entityClass = 'editor_Models_Foldertree';

    /**
     * @var editor_Models_Foldertree
     */
    protected $entity;

    /**
     * delivers the requested file to the browser
     * (non-PHPdoc)
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        $fileToDisplay = $this->getRequestedFileAbsPath();
        $file = new SplFileInfo($fileToDisplay);
        if (! $file->isFile()) {
            throw new ZfExtended_NotFoundException();
        }

        if(function_exists('apache_setenv')){
            apache_setenv('no-gzip', '1');
        }
        //header("HTTP/1.1 200 OK");
        //header('HTTP/1.1 304 Not Modified');
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header("Content-Type: ".$this->getMime($file));
        flush();
        readfile($file);
        exit;
    }

    /**
     * detects the file mime type
     * @param string $file
     * @return string
     */
    protected function getMime($file) {
        $mime = @finfo_open(FILEINFO_MIME);
        $result = finfo_file($mime, $file);
        if (empty($result)) {
            $result = 'application/octet-stream';
        }
        return $result;
    }

    /**
     * returns the absolute file path to the requested file, checks the given URL on ../ based attacks
     * @return string
     */
    protected function getRequestedFileAbsPath() {
        $session = new Zend_Session_Namespace();
        
        if(empty($session->taskGuid)){
            throw new ZfExtended_NotAuthenticatedException();
        }
        
        $config = Zend_Registry::get('config');

        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($session->taskGuid);

        $taskPath = $task->getAbsoluteTaskDataPath();
        $refDir = $taskPath.DIRECTORY_SEPARATOR.$config->runtimeOptions->import->referenceDirectory;
        $requestedFile = $this->getRequestedFileRelPath();
        $baseReal = realpath($refDir);
        $fileReal = realpath($refDir.$requestedFile);
        if($fileReal !== $baseReal.$requestedFile) {
            return null; //tryied hacking with ../ in PathName => send nothing
        }
        return $fileReal;
    }

    /**
     * returns the file path part of the REQUEST URL
     * @return string
     */
    protected function getRequestedFileRelPath() {
        $zcf = Zend_Controller_Front::getInstance();
        $urlBase = array();
        $urlBase[] = $zcf->getBaseUrl();
        $urlBase[] = $this->getRequest()->getModuleName();
        $urlBase[] = $this->getRequest()->getControllerName();
        $urlBase = join('/', $urlBase);
        $file = str_replace('!#START'.$urlBase, '', '!#START'.$zcf->getRequest()->getRequestUri());
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file); //URL to file system
        /* @var localEncoded ZfExtended_Controller_Helper_LocalEncoded */
        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );
        return $localEncoded->encode(urldecode($file));
    }

    /**
     * sends the reference file tree as JSON
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {
        $session = new Zend_Session_Namespace();
        $this->entity->loadByTaskGuid($session->taskGuid);
        //by passing output handling, output is already JSON
        $contextSwitch = $this->getHelper('ContextSwitch');
        $contextSwitch->setAutoSerialization(false);
        $this->getResponse()->setBody($this->entity->getReferenceTreeAsJson());
    }

    public function putAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }

    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}