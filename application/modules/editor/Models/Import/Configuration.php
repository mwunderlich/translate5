<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Contains all parameters for the planned import.
 * Since this class is serialized for the worker table, 
 *  it must not contain complex data!
 */
class editor_Models_Import_Configuration {
    /**
     * @var editor_Models_Languages language entity instance
     */
    public $sourceLang;
    
    /**
     * @var editor_Models_Languages language entity instance
     */
    public $targetLang;
    
    /**
     * @var editor_Models_Languages language entity instance
     */
    public $relaisLang;
    
    /**
     * concrete given source language
     * @var mixed
     */
    public $sourceLangValue;
    
    /**
     * concrete given target language
     * @var mixed
     */
    public $targetLangValue;
    
    /**
     * concrete given relais language
     * @var mixed
     */
    public $relaisLangValue;
    
    /**
     * @var string import folder, under which the to be imported folder and file hierarchy resides
     */
    public $importFolder;
    
    /**
     * @var boolean
     */
    public $isCheckRun = false;

    /**
     * initial userguid of the segments
     * @var string
     */
    public $userGuid;
    
    /**
     * initial username of the segments
     * @var string
     */
    public $userName;
    
    /**
     * needed internally for de/serialization 
     * @var string
     */
    protected $usedLanguagetype;
    
    /**
     * populates the language fields
     * @param mixed $source
     * @param mixed $target
     * @param mixed $relais
     * @param string $type
     */
    public function setLanguages($source, $target, $relais, string $type) {
        $this->sourceLangValue = $source;
        $this->targetLangValue = $target;
        $this->relaisLangValue = $relais;
        
        $this->usedLanguagetype = $type;
        
        $langFields = array('sourceLang' => $source, 'targetLang' => $target, 'relaisLang' => $relais);
        
        foreach($langFields as $key => $lang) {
            $langInst = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $langInst editor_Models_Languages */
            if(empty($lang) || !$langInst->loadLang($lang, $type)) {
                //null setzen wenn Sprache nicht gefunden. Das triggert einen Fehler in der validateParams dieser Klasse
                $langInst = null;
            }
            $this->{$key} = $langInst;
        }
    }
    
    /**
     * returns the numeric (DB) ID of the requested language type (source|target|relais)
     * @param string $language one of [source,target,relais]
     * @return number
     */
    public function getLanguageId(string $language) {
        $field = $language.'Lang';
        return isset($this->$field) ? $this->$field->getId() : 0;
    }
    
    /**
     * returns true if there is relais data and a language was found in the DB to the requested relais language
     * @return boolean
     */
    public function hasRelaisLanguage() {
        return !empty($this->relaisLang);
    }

    public function isValid($taskGuid) {
        $this->validateParams($taskGuid);
        $this->validateImportFolders();
    }
    
    /**
     * Gibt den absoluten Pfad (inkl. Import Root) zum Verzeichnis mit den zu lektorierenden Dateien zurück, berücksichtigt die proofRead bzw. Relaissprachen Config
     * @return string
     */
    public function getProofReadDir() {
        $config = Zend_Registry::get('config');
        $prefix = $this->importFolder;
        $proofReadDir = $config->runtimeOptions->import->proofReadDirectory;
        return $proofReadDir == '' ? $prefix : $prefix.DIRECTORY_SEPARATOR.$proofReadDir; 
    }
    
    /**
     * validiert / filtert die Get-Werte
     * @throws Zend_Exception
     */
    protected function validateParams($taskGuid){
        
        //if relais path does not exist, set $relaisLang to null, but keep relaisLangValue for validation
        $config = Zend_Registry::get('config');
        $dir = $this->importFolder.DIRECTORY_SEPARATOR.$config->runtimeOptions->import->relaisDirectory;
        if($this->relaisLangValue > 0 && !is_dir($dir)) {
            $this->relaisLang = null;
        }
        
        $_langErrors = array(
            'source' => 'Die übergebene Quellsprache %s ist ist ungültig.',
            'target' => 'Die übergebene Zielsprache %s ist ist ungültig.',
            'relais' => 'Die übergebene Relaissprache %s ist ist ungültig.',
        );
        
        $guidValidator = new ZfExtended_Validate_Guid();
        $validateUsername = new Zend_Validate_Regex('"[A-Za-z0-9 \-]+"');
        if(!$guidValidator->isValid($taskGuid)){
            throw new Zend_Exception('Die übergebene taskGuid '.$taskGuid.' ist keine valide GUID.');
        }
        if(!$guidValidator->isValid($this->userGuid)){
            throw new Zend_Exception('Die übergebene userGuid '.$this->userGuid.' ist keine valide GUID.');
        }
        if(!$validateUsername->isValid($this->userName)){
            throw new Zend_Exception('Der übergebene _userName '.$this->userName.' ist kein valider Username.');
        }
        if(is_null($this->sourceLang)){
            throw new Zend_Exception(sprintf($_langErrors['source'], $this->sourceLangValue));
        }
        if(is_null($this->targetLang)){
            throw new Zend_Exception(sprintf($_langErrors['target'], $this->targetLangValue));
        }
        if(!empty($this->relaisLangValue) && is_null($this->relaisLang)){
            throw new Zend_Exception(sprintf($_langErrors['relais'], $this->relaisLangValue));
        }
    }
    
    /**
     * validiert die nötigen Import Verzeichnisse
     * @throws Zend_Exception
     */
    protected function validateImportFolders(){
        $error = '';
        if(!is_dir($this->importFolder)){
            $error .= 'Der übergebene importRootFolder '.$this->importFolder.' existiert nicht.';
        }
        if(!is_dir($this->getProofReadDir()) || empty(glob($this->getProofReadDir().'/*'))){
            $error .= 'Der übergebene ProofReadFolder '.$this->getProofReadDir().' existiert nicht oder ist leer.';
        }
        
        if (empty($error)) {
            return;
        }
        
        if(Zend_Registry::isRegistered('rest_messages')) {
            $messages = Zend_Registry::get('rest_messages');
            /* @var $messages ZfExtended_Models_Messages */
            $messages->addError($error);
        }
        throw new Zend_Exception($error);
    }
    
    /**
     * after deserialization we have to resurrect the language objects
     */
    public function __wakeup() {
        $this->setLanguages($this->sourceLangValue, $this->targetLangValue, $this->relaisLangValue, $this->usedLanguagetype);
    }
    
    /**
     * To reduce serialization data we remove the language instances and keep only the scalar values
     * @return string
     */
    public function __sleep() {
        $this->sourceLang = $this->targetLang = $this->relaisLang = null;
        return array_keys(get_class_vars(get_class($this)));
    }
}
