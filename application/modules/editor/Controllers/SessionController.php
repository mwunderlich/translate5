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

/**
 * Wrapper for ZfExtended_SessionController
 * else RestRoutes, ACL authentication, etc. will not work.
 */
class editor_SessionController extends ZfExtended_SessionController {
    public function postAction() {
        if(!parent::postAction()) {
            return;
        }
        settype($this->data->taskGuid, 'string');
        $taskGuid = $this->getParam('taskGuid', $this->data->taskGuid);
        
        //if there is no taskGuid provided, we don't have to load one
        if(empty($taskGuid)) {
            return;
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $params = ['id' => $task->getId(), 'data' => '{"userState":"edit","id":'.$task->getId().'}'];
        $this->forward('put', 'task', 'editor', $params);
        
        // the static event manager must be used!
        $events = Zend_EventManager_StaticEventManager::getInstance();
        $events->attach('editor_TaskController', 'afterPutAction', function(Zend_EventManager_Event $event){
            //clearing the view vars added in Task::PUT keeps the old content (the session id and token) 
            $view = $event->getParam('view');
            $view->clearVars();
        });
    }
}