<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
* @package editor
* @version 1.0
*

/**
 * Provides the import data as an abstract interface to the import process
 */
abstract class editor_Models_Import_DataProvider_Abstract {
    const TASK_ARCHIV_ZIP_NAME = 'ImportArchiv.zip';
    protected $task;
    protected $taskPath;
    protected $importFolder;
    /**
     * DataProvider specific Checks (throwing Exceptions) and actions to prepare import data
     */
    abstract public function checkAndPrepare();

    /**
     * DataProvider specific method to create the import archive
     */
    abstract public function archiveImportedData();
    
    /**
     * returns the the absolute import path, mainly used by the import class
     * @return string 
     */
    public function getAbsImportPath(){
    	return $this->importFolder;
    }
    
    /**
     * creates a temporary folder to contain the import data 
     * @throws Zend_Exception
     */
    protected function checkAndMakeTempImportFolder() {
        $this->importFolder = $this->taskPath.DIRECTORY_SEPARATOR.'_tempImport';
        if(is_dir($this->importFolder)) {
            throw new Zend_Exception('Temporary directory for Task GUID ' . $this->task->getTaskGuid() . ' already exists!');
        }
        $msg = 'Temporary directory for Task GUID ' . $this->task->getTaskGuid() . ' could not be created!';
        $this->mkdir($this->importFolder, $msg);
    }
    
    /**
     * deletes the temporary import folder
     */
    protected function removeTempFolder() {
        /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                    'Recursivedircleaner'
        );
        if(isset($this->importFolder) && is_dir($this->importFolder)) {
            $recursivedircleaner->delete($this->importFolder);
        }
    }
    
    /**
     * exception throwing mkdir
     * @param string $path
     * @param string $errMsg
     * @throws Zend_Exception
     */
    protected function mkdir($path, $errMsg = null) {
        if(empty($errMsg)) {
            $errMsg = 'Could not create folder '.$path;
        }
        if(!@mkdir($path)) {
            throw new Zend_Exception($errMsg);
        }
    }
    
    /**
     * sets the internal used task object
     * @param editor_Models_Task $task
     */
    public function setTask(editor_Models_Task $task){
        $this->taskPath = $task->getAbsoluteTaskDataPath();
        $this->task = $task;
    }
    
    /**
     * returns the fix defined (=> final) archiveZipPath
     * @return string
     */
    protected final function getZipArchivePath() {
        return $this->taskPath.DIRECTORY_SEPARATOR.self::TASK_ARCHIV_ZIP_NAME;
    }
    
    /**
     * is called after import process by the import class. 
     */
    public function postImportHandler() {
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('beforeArchiveImportedData', $this, array());
        $this->archiveImportedData();
    }
    
    /**
     * stub method, is called after an execption occured in the import process. 
     * To be overridden.
     */
    public function handleImportException(Exception $e) {}
}