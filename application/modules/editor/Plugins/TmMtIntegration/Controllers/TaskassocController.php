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

/**
 * Controller for the Plugin TmMtIntegration Associations
 */
class editor_Plugins_TmMtIntegration_TaskassocController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Plugins_TmMtIntegration_Models_TmMtAssocIntegrationMeta';

    /**
     * @var editor_Plugins_TmMtIntegration_Models_TmMtAssocIntegrationMeta
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = array('id');
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        
        //if filtered for a taskGuid we merge the checked and also the TmMt information 
        
        $filter = $this->entity->getFilter();
        
        if(!$filter->hasFilter('taskGuid', $taskGuid)) { //handle the rest default case
            $this->view->rows = $this->entity->loadAll();
            $this->view->total = $this->entity->getTotalCount();
            return;
        }
        
        $tmmt = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Models_TmMt');
        /* @var $tmmt editor_Plugins_TmMtIntegration_Models_TmMt */
        $allTmmt = $tmmt->loadAll();
        $allCount = $tmmt->getTotalCount();
        
        $assocs = $this->entity->loadAll(); //filtered automaticly by taskGuid
        //reindex by tmmtId
        $assocsByTmmtId = array();
        foreach($assocs as $assoc) {
            $assocsByTmmtId[$assoc->tmmtId] = $assoc;
        }
        
        foreach($allTmmt as &$tmmt) {
            $tmmt->checked = !empty($assocsByTmmtId[$tmmt->id]);
            //FIXME merge / add missing data from asso to tmmt 
        }
        
        $this->view->rows = $allTmmt;
        $this->view->total = $allCount;
    }

    /**
     * for post requests we have to check the existance of the desired task first!
     * (non-PHPdoc)
     * @see ZfExtended_RestController::validate()
     */
    protected function validate() {
        if($this->_request->isPost()) {
            settype($this->data->taskGuid, 'string');
            $t = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $t editor_Models_Task */
            $t->loadByTaskGuid($this->data->taskGuid);
        }
        return parent::validate();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        //FIXME
        //this depends on what Aleks did already
        //possible way 1: only make PUT calls and use the tmmt ID as entity id for this controller
        //         way 2: don't sync the grid store, but make DELETE and POST calls manually with the tmmtAssoc data
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
    	$variable = $this->_request;
    	$parametri  = $variable->_params;
        parent::postAction();
        $this->addUserInfoToResult();
    }
    
    /**
     * adds the extended userinfo to the resultset
     */
    protected function addUserInfoToResult() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($this->entity->getUserGuid());
        $this->view->rows->login = $user->getLogin();
        $this->view->rows->firstName = $user->getFirstName();
        $this->view->rows->surName = $user->getSurName();
    }
}