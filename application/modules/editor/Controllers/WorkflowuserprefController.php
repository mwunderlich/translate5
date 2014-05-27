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
/**
 *
 */
class editor_WorkflowuserprefController extends ZfExtended_RestController {
    protected $entityClass = 'editor_Models_Workflow_Userpref';
    
    /**
     * @var editor_Models_Workflow_Userpref
     */
    protected $entity;
    
    /**
     * overridden to prepare data
     * (non-PHPdoc)
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
        parent::decodePutData();
        if($this->_request->isPost()) {
            unset($this->data->id); //don't set the ID from client side
            //a new default entry cannot be created:
            if(empty($this->data->workflowStep) && empty($this->data->userGuid)) {
                throw new ZfExtended_Models_Entity_NotAcceptableException();
            }
        }
        if($this->_request->isPut() && $this->entity->isDefault()) {
            unset($this->data->workflowStep); //don't update the workflowStep of the default entry
            unset($this->data->userGuid); //don't update the userGuid of the default entry
        }
    }
    
    /**
     * deletes the UserPref entry, ensures that the default entry cannot be deleted by API!
     * (non-PHPdoc)
     * @see ZfExtended_RestController::deleteAction()
     */
    public function deleteAction() {
        $this->entity->load($this->_getParam('id'));
        if($this->entity->isDefault()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        $this->entity->delete();
    }
}