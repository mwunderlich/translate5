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
* Gets the Import Data from a Zip File
*/
class editor_Models_Import_DataProvider_Zip extends editor_Models_Import_DataProvider_Abstract {
	protected $importZip;
	public function __construct($pathToZipFile){
		$this->importZip = $pathToZipFile;
	}

	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
	 */
	public function checkAndPrepare() {
	    $this->checkAndMakeTempImportFolder();
	    $this->unzip();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::archiveImportedData()
	 */
	public function archiveImportedData() {
	    $config = Zend_Registry::get('config');
	    if(!$config->runtimeOptions->import->createArchivZip){
	        unlink($this->importZip);
	        return;
	    }
	    $target = $this->getZipArchivePath();
	    if(file_exists($target)) {
	        throw new Zend_Exception('TaskData Import Archive Zip already exists: '.$target);
	    }
	    rename($this->importZip, $target);
	}

	/**
	 * extrahiert das geholte Zip File, bricht bei Fehlern ab
	 */
	protected function unzip() {
		$zip = new ZipArchive;
		if (!$zip->open($this->importZip)) {
			throw new Zend_Exception('Zip Datei ' . $this->importZip . ' konnte nicht geöffnet werden!');
		}
		if(!$zip->extractTo($this->importFolder)){
			throw new Zend_Exception('Zip Datei ' . $this->importZip . ' konnte nicht entpackt werden!');
		}
		$zip->close();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::postImportHandler()
	 */
	public function postImportHandler() {
	    parent::postImportHandler();
	    $this->removeTempFolder();
	}

	/**
	 * (non-PHPdoc)
	 * @see editor_Models_Import_DataProvider_Abstract::handleImportException()
	 */
	public function handleImportException(Exception $e) {
	    $this->removeTempFolder();
	}
}