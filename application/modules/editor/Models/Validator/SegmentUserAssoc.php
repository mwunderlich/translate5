<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

class editor_Models_Validator_SegmentUserAssoc extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for Segment User Assoc Entity
     */
    protected function defineValidators() {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        //comment = string, without length contrain. No validator needed / possible
        $this->addValidator('id', 'int');
        $this->addValidator('segmentId', 'int');
        $this->addValidator('userGuid', 'guid');
        $this->addValidator('taskGuid', 'guid');
        $this->addValidator('created', 'date', array('Y-m-d H:i:s'));
        $this->addValidator('modified', 'date', array('Y-m-d H:i:s'));
    }
}
