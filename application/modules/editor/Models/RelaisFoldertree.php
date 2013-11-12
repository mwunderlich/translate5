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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Erweitert Foldertree Klasse um die Methoden welche für den Relais Dateien Import benötigt werden
 */
class editor_Models_RelaisFoldertree extends editor_Models_Foldertree {
    const RELAIS_NOT_FOUND = 0; //keine Relais Datei gefunden
    const RELAIS_NOT_IMPORTED = 1; //gefunden aber noch importiert
    const RELAIS_IMPORTED = 2; //Relais Datei bereits importiert
    
    /**
     * Assoc Array, fileId => RelaisFileStatus
     * @var array
     */
    protected $relaisFilesStati = array();
   
    /**
     * Pfad zu den Relais Dateien
     * @var string
     */
    protected $relaisRootPath;
    
    /**
     * Schalter um spezfische Logik fürs Relais Dateien Checking an und abzuschalten
     * @var boolean
     */
    protected $checkCall = false;
    
    /**
     * Wenn true werden fehlende Relais Datein als Fehler geloggt
     * @var boolean
     */
    protected $logMissingFiles = null;

    /**
     * @var ZfExtended_Controller_Helper_LocalEncoded
     */
    protected $localEncoded = array();
    
    public function __construct(){
        parent::__construct();
        $config = Zend_Registry::get('config');
        $this->logMissingFiles = $config->runtimeOptions->import->reportOnNoRelaisFile;
        $this->localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        
        $this->setPathPrefix($config->runtimeOptions->import->relaisDirectory);
    }
    
    /**
     * Durchsucht das angegebene Relais Verzeichnis nach den im internen Baum gespeicherten Dateien 
     * und setzt bei Funden entsprechend den Relais File Status im internen Baum
     * @param string $importFolder
     * @return array
     */
    public function checkAndGetRelaisFiles(string $importFolder){
        $this->checkCall = true;
        $this->relaisRootPath = $importFolder.DIRECTORY_SEPARATOR.$this->_pathPrefix;
    	$this->getFilePathsNodeVisitor($this->objectTree);
        $this->checkCall = false;
        return $this->_paths;
    }
    
    /**
     * Wird im getFilePathsNodeVisitor für jede Datei aufgerufen
     * @param stdClass $child
     * @param string $path
     */
    protected function handleFile(stdClass $child, $path) {
    	parent::handleFile($child, $path);
    	if(! $this->checkCall){
    	    return;
    	}
    	$filepath = $path.$child->filename;
        $fullpath = $this->relaisRootPath.DIRECTORY_SEPARATOR.$this->localEncoded->encode($filepath);
        if(empty($child->relaisFileStatus)){
            $child->relaisFileStatus = file_exists($fullpath) ? self::RELAIS_NOT_IMPORTED : self::RELAIS_NOT_FOUND;
        }
        $this->logMissingFile($path, $child);
        $this->relaisFilesStati[$this->_pathPrefix.DIRECTORY_SEPARATOR.$filepath] = $child->relaisFileStatus;
    }
    
    /**
     * Loggt bei Bedarf die Relais Datei als fehlend
     * @param string $path
     * @param stdClass $child
     */
    protected function logMissingFile($path, stdClass $child) {
        if($child->relaisFileStatus !== self::RELAIS_NOT_FOUND 
             || !$this->logMissingFiles 
             || empty($child->isFile)){
            return;
        }
        
        $msg = 'Missing Relais File: "'.$path.$child->filename.'". Task: '.$this->getTaskGuid();
        /* @var $log ZfExtended_Log */
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        $log->logError($msg);
    }
    
    /**
     * Gibt eine Array mit allen Relais File Stati zu den ProofRead Dateien zurück 
     * @return array
     */
    public function getRelaisFileStati() {
        return $this->relaisFilesStati;
    }
    
    /**
     * @param string $path
     * @return boolean
     */
    public function isFileToImport(string $path) {
        return isset($this->relaisFilesStati[$path]) &&
            $this->relaisFilesStati[$path] == self::RELAIS_NOT_IMPORTED;  
    }
}