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
 */
/**
 * Imports the reference file data structure
 * 
 * Note to several unset($node->id) calls used in this class:
 *  In Working Files the ID ist afterwards generated by the sync to the files table.
 *  For the reference no id must be set, so that auto ids are generated on client side
 */
class editor_Models_Import_DirectoryParser_ReferenceFiles extends editor_Models_Import_DirectoryParser_WorkingFiles {
    /**
     * empty array disables file extension filter
     * @var array
     */
    protected $_importExtensionList = array();

    /**
     * Adds reference file specific infos to the tree node 
     * @param string $filename
     * @return stdClass
     */
    protected function getFileNode($filename) {
        $node = parent::getFileNode($filename);
        if($node->isFile) {
            $node->href = $node->path.$node->filename;
            $node->hrefTarget = '_blank';
        }
        unset($node->id); //@see class head comment
        return $node;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DirectoryParser_WorkingFiles::getDirectoryNode()
     */
    protected function getDirectoryNode($directory){
        $node = parent::getDirectoryNode($directory);
        unset($node->id); //@see class head comment
        return $node;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DirectoryParser_WorkingFiles::getInitialRootNode()
     */
    protected function getInitialRootNode() {
        $node = parent::getInitialRootNode();
        $node->path = 'referencefile';
        unset($node->id); //@see class head comment
        return $node;
    }
}