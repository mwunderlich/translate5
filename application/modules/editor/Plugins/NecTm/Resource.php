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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_NecTm_Resource extends editor_Models_LanguageResources_Resource {
    
    /**
     * @var editor_Plugins_NecTm_Service
     */
    protected $service;
    
    public function __construct(editor_Plugins_NecTm_Service $service) {
        $this->service = $service;
    }
    
    /**
     * Returns all tags that are offered to choose from when adding a NEC-TM LangageResource
     * in translate5:
     * - top-lecel-tags from config
     * - tags from NEC-TM stored (+ regularly synched) in our DB
     * @return array
     */
    public function getAllTags() {
        $tagsFromConfig = $this->service->getTopLevelTags();
        $m = ZfExtended_Factory::get('editor_Models_Tags');
        /* @var $m editor_Models_Tags */
        $tagsFromNEC = $m->loadByOrigin($this->service->getTagOrigin());
        // TODO: mergen und uniquen und dann return
    }
}