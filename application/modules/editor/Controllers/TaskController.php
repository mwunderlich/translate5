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
 *
 */
class editor_TaskController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_Task';
    
    /**
     * aktueller Datumsstring
     * @var string
     */
    protected $now;
    
    /**
     * logged in user
     * @var Zend_Session_Namespace
     */
    protected $user;

    /**
     * @var editor_Models_Task
     */
    protected $entity;
    
    /**
     * Cached map of userGuids to userNames
     * @var array
     */
    protected $cachedUserInfo = array();
    
    /**
     * loadAll counter buffer
     * @var integer
     */
    protected $totalCount;
    
    /**
     * Specific Task Filter Class to use
     * @var string
     */
    protected $filterClass = 'editor_Models_Filter_TaskSpecific';
    
    /**
     * @var editor_Workflow_Abstract 
     */
    protected $workflow;
    
    /**
     * @var editor_Workflow_Manager 
     */
    protected $workflowManager;
    
    /**
     * @var ZfExtended_Acl 
     */
    protected $acl;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var editor_Models_Import_UploadProcessor
     */
    protected $upload;
    
    /**
     * @var Zend_Config
     */
    protected $config;

    public function init() {
        parent::init();
        $this->now = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $this->acl = ZfExtended_Acl::getInstance();
        $this->user = new Zend_Session_Namespace('user');
        $this->workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->upload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor');
        $this->config = Zend_Registry::get('config');
    }
    
    /**
     * init the internal used workflow
     * @param string $wfId workflow ID. optional, if omitted use the workflow of $this->entity
     */
    protected function initWorkflow($wfId = null) {
        if(empty($wfId)) {
            $wfId = $this->entity->getWorkflow();
        }
        $this->workflow = $this->workflowManager->getCached($wfId);
    }
    
    /**
     * 
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $unlockedTasks = $this->entity->cleanupLockedJobs();
        $userGuid = $this->user->data->userGuid;
        
        //we clean up ALL tasks belonging to the actual user, 
        //since if this action is called he has left the task (TRANSLATE-91)
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $tua->cleanupLocked();
        
        //set default sort
        $f = $this->entity->getFilter();
        $f->hasSort() || $f->addSort('orderdate', true);
        
        $this->view->rows = $this->loadAll();
        $this->view->total = $this->totalCount;
    }
    
    /**
     * uses $this->entity->loadAll, but unsets qmSubsegmentFlags for all rows and
     * set qmSubEnabled for all rows
     */
    public function loadAll()
    {
        $isAllowedToLoadAll = $this->isAllowed('loadAllTasks');
        $filter = $this->entity->getFilter();
        $filter->convertStates($isAllowedToLoadAll);
        $assocFilter = $filter->isUserAssocNeeded();
        if(!$assocFilter && $isAllowedToLoadAll) {
            $this->totalCount = $this->entity->getTotalCount();
            $rows = $this->entity->loadAll();
        }
        else {
            $filter->setUserAssocNeeded();
            $this->totalCount = $this->entity->getTotalCountByUserAssoc($this->user->data->userGuid, $isAllowedToLoadAll);
            $rows = $this->entity->loadListByUserAssoc($this->user->data->userGuid, $isAllowedToLoadAll);
        }
        
        $taskGuids = array_map(function($item){
            return $item['taskGuid'];
        },$rows);
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos($taskGuids, $userAssocInfos);
        
        foreach ($rows as &$row) {
            $this->initWorkflow($row['workflow']);
            //adding QM SubSegment Infos to each Task
            $row['qmSubEnabled'] = false;
            if($this->config->runtimeOptions->editor->enableQmSubSegments &&
                    !empty($row['qmSubsegmentFlags'])) { 
                $row['qmSubEnabled'] = true;
            }
            unset($row['qmSubsegmentFlags']);
            
            $this->addUserInfos($row, $row['taskGuid'], $userAssocInfos, $allAssocInfos);
        }
        return $rows;
    }
    
    /**
     * Fetch an array with Task User Assoc Data for the currently logged in User.
     * Returns an array with an entry for each task, key is the taskGuid
     * @return array
     */
    protected function getUserAssocInfos($taskGuids, &$userAssocInfos) {
        $userAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userAssoc editor_Models_TaskUserAssoc */
        $userGuid = $this->user->data->userGuid;
        $assocs = $userAssoc->loadByTaskGuidList($taskGuids);
        $res = array();
        foreach($assocs as $assoc) {
            if(!isset($res[$assoc['taskGuid']])) {
                $res[$assoc['taskGuid']] = array(); 
            }
            if($userGuid == $assoc['userGuid']) {
                $userAssocInfos[$assoc['taskGuid']] = $assoc;
            }
            $userInfo = $this->getUserinfo($assoc['userGuid']);
            $assoc['userName'] = $userInfo['surName'].', '.$userInfo['firstName'];
            $assoc['login'] = $userInfo['login'];
            //set only not pmOverrides
            if(empty($assoc['isPmOverride'])) {
                $res[$assoc['taskGuid']][] = $assoc;
            }
        }
        $userSorter = function($first, $second){
            if($first['userName'] > $second['userName']) {
                return 1;
            }
            if($first['userName'] < $second['userName']) {
                return -1;
            }
            return 0;
        };
        foreach($res as $taskGuid => $taskUsers) {
            usort($taskUsers, $userSorter);
            $res[$taskGuid] = $taskUsers; 
        }
        return $res;
    }

    /**
     * replaces the userGuid with the username
     * Doing this on client side would be possible, but then it must be ensured that UsersStore is always available and loaded before TaskStore. 
     * @param string $userGuid
     */
    protected function getUserinfo($userGuid) {
        $notfound = array(); //should not be, but can occur after migration of old data!
        if(empty($userGuid)) {
            return $notfound;
        }
        if(isset($this->cachedUserInfo[$userGuid])) {
            return $this->cachedUserInfo[$userGuid];
        }
        if(empty($this->tmpUserDb)) {
            $this->tmpUserDb = ZfExtended_Factory::get('ZfExtended_Models_Db_User');
            /* @var $this->tmpUserDb ZfExtended_Models_Db_User */
        }
        $s = $this->tmpUserDb->select()->where('userGuid = ?', $userGuid);
        $row = $this->tmpUserDb->fetchRow($s);
        if(!$row) {
            return $notfound; 
        }
        $this->cachedUserInfo[$userGuid] = $row->toArray();
        return $row->toArray(); 
    }
    
    /**
     * returns the commonly used username: Firstname Lastname (login)
     * @param array $userinfo
     */
    protected function getUsername(array $userinfo) {
        if(empty($userinfo)) {
            return '- not found -'; //should not be, but can occur after migration of old data!
        }
        return $userinfo['firstName'].' '.$userinfo['surName'].' ('.$userinfo['login'].')';
    }

    /**
     * creates a task and starts import of the uploaded task files 
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        $this->entity->init();
        //FIXME woher kommt der default workflow des tasks beim import???
        //$this->decodePutData(); → not needed, data was set directly out of params because of file upload
        $this->data = $this->_getAllParams();
        settype($this->data['wordCount'], 'integer');
        settype($this->data['enableSourceEditing'], 'boolean');
        $this->data['pmGuid'] = $this->user->data->userGuid;
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        $pm->init((array)$this->user->data);
        $this->data['pmName'] = $pm->getUsernameLong();
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        $this->entity->createTaskGuidIfNeeded();
        
        //init workflow id for the task
        $defaultWorkflow = $this->config->runtimeOptions->import->taskWorkflow;
        $this->entity->setWorkflow($this->workflowManager->getIdToClass($defaultWorkflow));
        
        if($this->validate()) {
            $this->initWorkflow();
            //$this->entity->save(); => is done by the import call!
            $this->processUploadedFile();
            $this->workflow->doImport($this->entity);
            $this->workflowManager->initDefaultUserPrefs($this->entity);
            //reload because entityVersion was changed by above workflow and workflow manager calls
            $this->entity->load($this->entity->getId());
            $this->view->success = true;
            $this->view->rows = $this->entity->getDataObject();
        }
    }

    /**
     * imports the uploaded file
     * @throws Exception
     */
    protected function processUploadedFile() {
        /* 
        //auskommentiert, da Serverabsturz bei inetsolutions, Zweck war die Sicherstellugn dass immer nur ein Import zur gleichen Zeit läuft.
        $config = Zend_Registry::get('config');
        $flagFile = $config->resources->cachemanager->zfExtended->backend->options->cache_dir.'/importRunning';
        while(file_exists($flagFile)){
            if(time()-filemtime($flagFile)>3600){
                unlink($flagFile);
            }
            sleep(1);
        }
        file_put_contents($flagFile, $this->getGuid());
        */
        $p = (object) $this->_request->getParams();
        
        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setEdit100PercentMatches((bool) $this->entity->getEdit100PercentMatch());
        $import->setUserInfos($this->user->data->userGuid, $this->user->data->userName);

        $import->setLanguages(
                        $this->entity->getSourceLang(), 
                        $this->entity->getTargetLang(), 
                        $this->entity->getRelaisLang(), 
                        editor_Models_Languages::LANG_TYPE_ID);
        $import->setTask($this->entity);
        $dp = $this->upload->getDataProvider();
        
        try {
            $import->import($dp);
        }
        catch (Exception $e) {
            $import->handleImportException($e, $dp);
            throw $e;
        }
        #auskommentiert, da Serverabsturz bei inetsolutions
        //if(file_exists($flagFile))unlink($flagFile);
    }
    
    /**
     * 
     * currently taskController accepts only 2 changes by REST
     * - set locked: this sets the session_id implicitly and in addition the 
     *   corresponding userGuid, if the passed locked value is set
     *   if locked = 0, task is unlocked
     * - set finished: removes locked implictly, and sets the userGuid of the "finishers" 
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $this->entity->load($this->_getParam('id'));
        
        if($this->entity->isImporting()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        $taskguid = $this->entity->getTaskGuid();
        
        $oldTask = clone $this->entity;
        $this->decodePutData();
        if(isset($this->data->enableSourceEditing)){
            $this->data->enableSourceEditing = (boolean)$this->data->enableSourceEditing;
        }
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        $this->entity->validate();
        $this->initWorkflow();
        
        $mayLoadAllTasks = $this->isAllowed('loadAllTasks');
        $tua = $this->workflow->getTaskUserAssoc($taskguid, $this->user->data->userGuid);
        if(!$mayLoadAllTasks &&
                ($this->isOpenTaskRequest(true)&&
                    !$this->workflow->isWriteable($tua)
                || $this->isOpenTaskRequest(false,true)&&
                    !$this->workflow->isReadable($tua)
                )
           ){
            //if the task was already in session, we must delete it. 
            //If not the user will always receive an error in JS, and would not be able to do anything.
            $this->entity->unregisterInSession(); //FIXME XXX the changes in the session made by this method is not stored in the session!
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        //opening a task must be done before all workflow "do" calls which triggers some events
        $this->openAndLock();
        
        $this->workflow->doWithTask($oldTask, $this->entity);
        
        if($oldTask->getState() != $this->entity->getState()) {
            editor_Models_LogTask::createWithUserGuid($taskguid, $this->entity->getState(), $this->user->data->userGuid);
        }
        
        //updateUserState does also call workflow "do" methods!
        $this->updateUserState($this->user->data->userGuid);
        
        //closing a task must be done after all workflow "do" calls which triggers some events
        $this->closeAndUnlock();
        
        $this->entity->save();
        $obj = $this->entity->getDataObject();
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos(array($taskguid), $userAssocInfos);
        
        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj; 
        $this->addUserInfos($row, $taskguid, $userAssocInfos, $allAssocInfos);
            
        $this->view->rows = (object)$row;
        if($this->isOpenTaskRequest()){
            $this->addQmSubToResult();
        }
        else {
            unset($this->view->rows->qmSubsegmentFlags);
        }
    }
    
    /**
     * Adds additional user based infos to the given array
     * @param array $row gets the row to modify as reference
     * @param string $taskguid
     * @param array $userAssocInfos
     * @param array $allAssocInfos
     */
    protected function addUserInfos(array &$row, $taskguid, array $userAssocInfos, array $allAssocInfos) {
        $isEditAll = $this->isAllowed('editAllTasks');
        //Add actual User Assoc Infos to each Task
        if(isset($userAssocInfos[$taskguid])) {
            $row['userRole'] = $userAssocInfos[$taskguid]['role'];
            $row['userState'] = $userAssocInfos[$taskguid]['state'];
            $row['userStep'] = $this->workflow->getStepOfRole($row['userRole']);
        }
        elseif($isEditAll && isset($this->data->userState)) {
            $row['userState'] = $this->data->userState; //returning the given userState for usage in frontend
        }
        
        //Add all User Assoc Infos to each Task
        if(isset($allAssocInfos[$taskguid])) {
            $reducer = function($accu, $item) {
                return $accu || !empty($item['usedState']);
            };
            $row['isUsed'] = array_reduce($allAssocInfos[$taskguid], $reducer, false);
            $row['users'] = $allAssocInfos[$taskguid];
        }
        
        $row['lockingUsername'] = $this->getUsername($this->getUserinfo($row['lockingUser']));
        
        $fields = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $fields editor_Models_SegmentField */
        
        $userPref = ZfExtended_Factory::get('editor_Models_Workflow_Userpref');
        /* @var $userPref editor_Models_Workflow_Userpref */
        
        //we load alls fields, if we are in taskOverview and are allowed to edit all 
        // or we have no userStep to filter / search by. 
        // No userStep means indirectly that we do not have a TUA (pmCheck)
        if(!$this->entity->isRegisteredInSession() && $isEditAll || empty($row['userStep'])) {
            $row['segmentFields'] = $fields->loadByTaskGuid($taskguid);
            //the pm sees all, so fix userprefs
            $userPref->setAnonymousCols(false);
            $userPref->setVisibility($userPref::VIS_SHOW);
            $allFields = array_map(function($item) { 
                return $item['name']; 
            }, $row['segmentFields']);
            $userPref->setFields(join(',', $allFields));
        } else {
            $wf = $this->workflow;
            $userPref->loadByTaskUserAndStep($taskguid, $wf::WORKFLOW_ID, $this->user->data->userGuid, $row['userStep']);
            $row['segmentFields'] = $fields->loadByUserPref($userPref);
        }
        $row['userPrefs'] = array($userPref->getDataObject());
        
        //$row['segmentFields'] = $fields->loadByCurrentUser($taskguid);
        foreach($row['segmentFields'] as $key => &$field) {
            //TRANSLATE-318: replacing of a subpart of the column name is a client specific feature
            $needle = $this->config->runtimeOptions->segments->fieldMetaIdentifier;
            if(!empty($needle)) {
                $field['label'] = str_replace($needle, '', $field['label']);
            }
            $field['label'] = $this->translate->_($field['label']);
        } 
        if(empty($this->segmentFieldManager)) {
            $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        }
        //sets the information if this task has default segment field layout or not
        $row['defaultSegmentLayout'] = $this->segmentFieldManager->isDefaultLayout(array_map(function($field){
            return $field['name'];
        }, $row['segmentFields']));
    }
    
    /**
     * returns true if PUT Requests opens a task for editing or readonly
     * 
     * - its not allowed to set both parameters to true
     * @param boolean $editOnly if set to true returns true only if its a real editing (not readonly) request
     * @param boolean $viewOnly if set to true returns true only if its a readonly request
     * 
     * FIXME Diese Methode und die noch nicht existierende isCloseTaskRequest in den Workflow packen und in this->closeAndUnlock integrieren.
     *          Dabei auch die fehlenden task stati waiting, end,open mit in isCloseTaskRequest integrieren !
     *           Ebenfalls die STATES nach workflow abstract umziehen, States dokumentieren.
     * @return boolean
     */
    protected function isOpenTaskRequest($editOnly = false,$viewOnly = false) {
        if(empty($this->data->userState)) {
            return false;
        }
        if($editOnly && $viewOnly){
            throw new Zend_Exception('editOnly and viewOnly can not both be true');
        }
        $s = $this->data->userState;
        $workflow = $this->workflow;
        return $editOnly && $s == $workflow::STATE_EDIT 
           || !$editOnly && ($s == $workflow::STATE_EDIT || $s == $workflow::STATE_VIEW)
           || $viewOnly && $s == $workflow::STATE_VIEW;
    }
    
    /**
     * locks the current task if its an editing request
     * stores the task as active task if its an opening or an editing request
     */
    protected function openAndLock() {
        $session = new Zend_Session_Namespace();
        if($this->isOpenTaskRequest(true)){
            if(!$this->entity->lock($this->now)){
                $workflow = $this->workflow;
                $this->data->userState = $workflow::STATE_VIEW;
            }
        }
        if($this->isOpenTaskRequest()){
            $this->entity->createMaterializedView();
            $this->entity->registerInSession($this->data->userState);
        }
    }
    
    /**
     * unlocks the current task if its an request that closes the task (set state to open, end, finish)
     * removes the task from session
     */
    protected function closeAndUnlock() {
        $workflow = $this->workflow;
        $closingStates = array(
            $workflow::STATE_FINISH,
            $workflow::STATE_OPEN
        );
        $task = $this->entity;
        $hasState = !empty($this->data->userState);
        $isEnding = isset($this->data->state) && $this->data->state == $task::STATE_END;
        $resetToOpen = $hasState && $this->data->userState == $workflow::STATE_EDIT && $isEnding;
        if($resetToOpen) {
            //This state change will be saved at the end of this method.
            $this->data->userState = $workflow::STATE_OPEN;
        }
        if(!$isEnding && (!$hasState || !in_array($this->data->userState, $closingStates))){
            return;
        }
        if($this->entity->getLockingUser() == $this->user->data->userGuid) {
            if(!$this->entity->unlock()){
                throw new Zend_Exception('task '.$this->entity->getTaskGuid().
                        ' could not be unlocked by user '.$this->user->data->userGuid);
            }
        }
        $this->entity->unregisterInSession();
        
        if($resetToOpen) {
            $this->updateUserState($this->user->data->userGuid, true);
        }
    }
    
    /**
     * Updates the transferred User Assoc State to the given userGuid (normally the current user)
     * Per Default all state changes trigger something in the workflow. In some circumstances this should be disabled.
     * @param string $userGuid
     * @param boolean $disableWorkflowEvents optional, defaults to false
     */
    protected function updateUserState(string $userGuid, $disableWorkflowEvents = false) {
        if(empty($this->data->userState)) {
            return;
        }
        
        $isEditAllTasks = $this->isAllowed('editAllTasks');
        $isOpen = $this->isOpenTaskRequest();
        $isPmOverride = false;
        
        $taskGuid = $this->entity->getTaskGuid();
        
        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        try {
            $userTaskAssoc->loadByParams($userGuid,$taskGuid);
            $isPmOverride = (boolean) $userTaskAssoc->getIsPmOverride();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            if(! $isEditAllTasks){
                throw $e;
            }
            $userTaskAssoc->setUserGuid($userGuid);
            $userTaskAssoc->setTaskGuid($taskGuid);
            $userTaskAssoc->setRole('');
            $userTaskAssoc->setState('');
            $isPmOverride = true;
            $userTaskAssoc->setIsPmOverride($isPmOverride);
        }

        $oldUserTaskAssoc = clone $userTaskAssoc;
        
        if($isOpen){
            $session = new Zend_Session_Namespace();
            $userTaskAssoc->setUsedInternalSessionUniqId($session->internalSessionUniqId);
            $userTaskAssoc->setUsedState($this->data->userState);
        } else {
            if($isPmOverride && $isEditAllTasks) {
                editor_Models_LogTask::createWithUserGuid($taskGuid, $this->data->userState, $this->user->data->userGuid);
                $userTaskAssoc->deletePmOverride();
                return;
            }
            $userTaskAssoc->setUsedInternalSessionUniqId(null);
            $userTaskAssoc->setUsedState(null);
        }
        
        if($this->workflow->isStateChangeable($userTaskAssoc)) {
            $userTaskAssoc->setState($this->data->userState);
        }
        
        if(!$disableWorkflowEvents) {
            $this->workflow->triggerBeforeEvents($oldUserTaskAssoc, $userTaskAssoc);
        }
        $userTaskAssoc->save();
        
        if(!$disableWorkflowEvents) {
            $this->workflow->doWithUserAssoc($oldUserTaskAssoc, $userTaskAssoc);
        }
        
        if($oldUserTaskAssoc->getState() != $this->data->userState){
            editor_Models_LogTask::createWithUserGuid($taskGuid, $this->data->userState, $this->user->data->userGuid);
        }
    }
    
    /**
     * Adds the Task Specific QM SUb Segment Infos to the request result.
     * Not usable for indexAction, must be called after entity->save and this->view->rows = Data
     */
    protected function addQmSubToResult() {
        $qmSubFlags = $this->entity->getQmSubsegmentFlags();
        $this->view->rows->qmSubEnabled = false;
        if($this->config->runtimeOptions->editor->enableQmSubSegments &&
                !empty($qmSubFlags)) { 
            $this->view->rows->qmSubFlags = $this->entity->getQmSubsegmentIssuesTranslated(false);
            $this->view->rows->qmSubSeverities = $this->entity->getQmSubsegmentSeveritiesTranslated(false);
            $this->view->rows->qmSubEnabled = true;
        }
        unset($this->view->rows->qmSubsegmentFlags);
    }
    
    /**
     * gets and validates the uploaded zip file
     */
    protected function additionalValidations() {
        $this->upload->initAndValidate();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        $taskguid = $this->entity->getTaskGuid();
        $this->initWorkflow();
        
        $obj = $this->entity->getDataObject();
        
        $userAssocInfos = array();
        $allAssocInfos = $this->getUserAssocInfos(array($taskguid), $userAssocInfos);
        
        //because we are mixing objects (getDataObject) and arrays (loadAll) as entity container we have to cast here
        $row = (array) $obj; 
        $this->addUserInfos($row, $taskguid, $userAssocInfos, $allAssocInfos);
            
        $this->view->rows = (object)$row;
        unset($this->view->rows->qmSubsegmentFlags);
    }
    
    public function deleteAction() {
        $this->entity->load($this->_getParam('id'));
        if($this->entity->isImporting()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        return parent::deleteAction();
    }
    
    /**
     * does the export as zip file.
     */
    public function exportAction() {
        parent::getAction();
        
        if($this->entity->isImporting()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        $diff = (boolean)$this->getRequest()->getParam('diff');

        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var $export editor_Models_Export */
        
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        
        if(!$export->setTaskToExport($this->entity, $diff)){
            //@todo: this should show up in JS-Frontend in a nice way
            echo $translate->_(
                    'Derzeit läuft bereits ein Export für diesen Task. Bitte versuchen Sie es in einiger Zeit nochmals.');
            exit;
        }
        $zipFile = $export->exportToZip();
        if($diff) {
            $suffix = $translate->_(' - mit Aenderungen nachverfolgen.zip');
        }
        else {
            $suffix = '.zip';
        }

        // disable layout and view
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: application/zip', TRUE);
        header('Content-Disposition: attachment; filename="'.$this->entity->getTasknameForDownload($suffix).'"');
        readfile($zipFile);
        exit;
    }
    
    /**
     * checks if currently logged in user is allowed to access the given ressource
     * shortcut method for convience
     * @param string $ressource
     * @return boolean
     */
    protected function isAllowed($ressource) {
        return $this->acl->isInAllowedRoles($this->user->data->roles, $ressource);
    }
}
