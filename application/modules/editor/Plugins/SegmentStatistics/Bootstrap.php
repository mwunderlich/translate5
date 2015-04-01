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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Plugin Bootstrap for Segment Statistics Plugin
 */
class editor_Plugins_SegmentStatistics_Bootstrap extends ZfExtended_Plugin_Abstract {
    public function init() {
        $this->blocks('editor_Plugins_SegmentStatistics_BootstrapEditableOnly');
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterImportCreateStat'), -90);
        //priority -10000 in order to always allow other plugins to modify meta-data before writer runs
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleImportWriteStat'), -10000);
        $this->eventManager->attach('editor_Models_Export', 'afterExport', array($this, 'handleAfterExport'), -10000);
        $this->eventManager->attach('editor_Models_Export', 'afterExport', array($this, 'handleExportWriteStat'), -10010);
    }
    
    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterImportCreateStat(Zend_EventManager_Event $event) {
        $this->callWorker($event->getParam('task'), 'editor_Plugins_SegmentStatistics_Worker', editor_Plugins_SegmentStatistics_Worker::TYPE_IMPORT);
    }
    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleImportWriteStat(Zend_EventManager_Event $event) {
        $this->callWorker($event->getParam('task'), 'editor_Plugins_SegmentStatistics_WriteStatisticsWorker', editor_Plugins_SegmentStatistics_Worker::TYPE_IMPORT);
    }
    /**
     * handler for event: editor_Models_Export#afterExport
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterExport(Zend_EventManager_Event $event) {
        $this->callWorker($event->getParam('task'), 'editor_Plugins_SegmentStatistics_Worker', editor_Plugins_SegmentStatistics_Worker::TYPE_EXPORT);
    }
    /**
     * handler for event: editor_Models_Export#afterExport
     * @param $event Zend_EventManager_Event
     */
    public function handleExportWriteStat(Zend_EventManager_Event $event) {
        $this->callWorker($event->getParam('task'), 'editor_Plugins_SegmentStatistics_WriteStatisticsWorker', editor_Plugins_SegmentStatistics_Worker::TYPE_EXPORT);
    }
    
    /**
     * @param editor_Models_Task $task
     * @param string $worker worker class name
     * @param string $type im- or export
     */
    protected function callWorker(editor_Models_Task $task, $worker, $type) {
        $worker = ZfExtended_Factory::get($worker);
        /* @var $worker editor_Plugins_SegmentStatistics_Worker */
        $worker->init($task->getTaskGuid(), array('type' => $type));
        $worker->queue();
    }
}