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
 * Gets the Import Data from a directory
 * the responsibility to clean up / delete the given Import Directory is not part of this class!
 */
class editor_Models_Import_DataProvider_Directory  extends editor_Models_Import_DataProvider_Abstract {
    /**
     * @param string $pathToImportDirectory
     */
    public function __construct($pathToImportDirectory){
        $this->importFolder = $pathToImportDirectory;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(){
        if(!is_dir($this->importFolder)){
        	throw new Zend_Exception('Der übergebene importRootFolder '.$this->importFolder.' existiert nicht.');
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::archiveImportedData()
     */
    public function archiveImportedData() {
        $config = Zend_Registry::get('config');
        if(!$config->runtimeOptions->import->createArchivZip){
        	return;
        }
        $filter = new Zend_Filter_Compress(array(
            'adapter' => 'Zip',
            'options' => array(
                'archive' => $this->taskPath.DIRECTORY_SEPARATOR.'ImportArchive.zip'
            ),
        ));
        if(!$filter->filter($this->importFolder)){
            throw new Zend_Exception('Could not create export-zip of task '.$this->taskGuid.'.');
        }
    }
}