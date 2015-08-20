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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * @see readme.md in the transit plugin directory!
 * 
 * Parsed mit editor_Models_Import_FileParser_Transit geparste Dateien für den Export
 */
class editor_Models_Export_FileParser_TransitInfoField {
    const TARGET_TYPE_EDITED = 'edited';
    const TARGET_TYPE_ORIGINAL = 'original';
    
    /**
     * @var string
     */
    protected $transitInfoString;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * @var boolean
     */
    protected $debug = false;
    
    /**
     * Counter how often a term with a specific gid is used in source, grouped by trans[Not]Found
     * @var array
     */
    protected $sourceGidsStatCount = array();
    
    /**
     * Counter how often a term with a specific gid is used in target
     * @var array
     */
    protected $targetGidsCount = array();
    
    /**
     * Maps the source mids to their groupid
     * @var array
     */
    protected $midToGroupMap = array();
    
    /**
     * Terms of a gid in source
     * @var array
     */
    protected $gidTermsSource = array();
    
    /**
     * Terms of a gid in source
     * @var array
     */
    protected $gidTermsTarget = array();
    
    /**
     * @var editor_Models_Term
     */
    protected $termModel;

    public function __construct(editor_Models_Task $task, Zend_Config $config, editor_Models_Segment $segment, ZfExtended_Zendoverwrites_Translate $translate) {
        $this->task = $task;
        $this->config = $config;
        $this->segment = $segment;
        $this->translate = $translate;
        $this->debug = ZfExtended_Debug::hasLevel('export', 'transit');
    }
    
    /**
     * Add the configured TransitInfoFields to the given string and returns it
     * @param string $text
     * @return string
     */
    public function addInfos($text) {
        $this->transitInfoString = $text;
        $this->infoFieldAddDate();
        $this->infoFieldAddStatus();
        $this->infoFieldAddTerms();
        if(ZfExtended_Debug::hasLevel('export', 'transit', 2)) {
            $id = $this->segment->getId();
            $mid = $this->segment->getMid();
            error_log("Add Transit Info to Segment Id: ".$id." Mid: ".$mid." Info: ".$this->transitInfoString);
        }
        return $this->transitInfoString;
    }
    
    /**
     * Add the Status to the info field
     */
    protected function infoFieldAddStatus() {
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->manualStatus !== 1){
            return;
        }
        $stateId = $this->segment->getStateId();
        if(empty($stateId)){
            $state = 'NO_QUALITY_STATE_SET_BY_USER';
        }
        else{
            $state = $this->config->runtimeOptions->segments->stateFlags->$stateId;
        }
        $this->transitInfoString .= ' '.$state;
    }
    
    /**
     * Adds a date string to the infoFieldContent String, only if enabled
     */
    protected function infoFieldAddDate() {
        //if no transit plugin config exists, exit
        if(!isset($this->config->runtimeOptions->plugins->transit)) {
            return;
        }
        $transitConfig = $this->config->runtimeOptions->plugins->transit;
        
        //if config is disabled, exit
        if((int)$transitConfig->writeInfoField->exportDate !== 1){
            return;
        }
        
        //use configured value or if empty now()
        if(empty($transitConfig->writeInfoField->exportDateValue)){
            $date = time();
        }
        else {
            $date = strtotime($transitConfig->writeInfoField->exportDateValue);
        }
        $session = new Zend_Session_Namespace();
        if(preg_match('"^de"i', $session->locale) === 1){
            $this->transitInfoString .= date("d.m.Y", $date).':';
        }
        else{
            $this->transitInfoString .= date("Y-m-d", $date).':';
        }
    }
    
    /**
     * Add the changed terminology to the notice string, for Details see TRANSLATE-477
     */
    protected function infoFieldAddTerms() {
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->termsWithoutTranslation !== 1){
            return;
        }
        
        $taskGuid = $this->task->getTaskGuid();
        $targetLang = $this->task->getTargetLang();
        $this->termModel = ZfExtended_Factory::get('editor_Models_Term');
        //<div class="term admittedTerm" id="term_193_es-ES_1-6" title="Spanischer Beschreibungstexttest">tamiz de cepillos rotativos</div>

        $sourceOrigText = $this->segment->getFieldOriginal(editor_Models_SegmentField::TYPE_SOURCE);
        $sourceTermsUsed = $this->termModel->getTermInfosFromSegment($sourceOrigText);
        //fetch all terms found in source
        $this->sourceGidsStatCount = array();
        $this->targetGidsCount = array(
                self::TARGET_TYPE_EDITED => array(), 
                self::TARGET_TYPE_ORIGINAL => array(),
        );
        foreach($sourceTermsUsed as $termInfo) {
            //fetch terms and their GIDs from source, count occurences of the several terms grouped by gid and splitted by trans[Not]Found
            $this->countSourceGroupUsage($termInfo);
        }

        $targetTerms = array();
        //fetch terms and their GIDs from target orig, count occurences of the several terms by gid
        $targetOrig = $this->segment->getFieldOriginal(editor_Models_SegmentField::TYPE_TARGET);
        $targetOrigTermMids = $this->termModel->getTermMidsFromSegment($targetOrig);
        foreach($targetOrigTermMids as $mid) {
            $this->countTargetGroupUsage(self::TARGET_TYPE_ORIGINAL, $mid);
        }
        
        //fetch terms and their GIDs from target edited, count occurences of the several terms by gid
        $targetEdited = $this->segment->getFieldEdited(editor_Models_SegmentField::TYPE_TARGET);
        $targetEditedTermMids = $this->termModel->getTermMidsFromSegment($targetEdited);
        foreach($targetEditedTermMids as $mid) {
            $this->countTargetGroupUsage(self::TARGET_TYPE_EDITED, $mid);
        }
        
        $this->logFoundMismatch();

        //We add only terms to the info field, which were converted from red to blue
        //that means we track the GIDs of all added and increased terms (empty in orignal, > 0 in edit)
        //in addition the corresponding GID must exist in source and 
        //the targetOriginal GID count must be lesser then the source GID count. 
        //If not this is an additional term (more term usages in target than in source) 
        $sourceTermsToTrack = array();
        $targetTermsToTrack = array();
        foreach($this->targetGidsCount[self::TARGET_TYPE_EDITED] as $gid => $count) {
            if(empty($this->targetGidsCount[self::TARGET_TYPE_ORIGINAL][$gid])) {
                $origCount = 0;
            }
            else {
                $origCount = $this->targetGidsCount[self::TARGET_TYPE_ORIGINAL][$gid];
            }
            if(empty($this->sourceGidsStatCount[$gid])) {
                $sourceCount = 0;
            }
            else {
                $sourceCount = $this->sourceGidsStatCount[$gid]['transFound'];
            }
            if($count > 0 && $count > $origCount && $origCount < $sourceCount){
                //We track all available source terms to the affected GIDs,
                $sourceTermsToTrack = array_merge($sourceTermsToTrack, $this->gidTermsSource[$gid]);
                //We track all available target terms to the affected GIDs,
                $targetTermsToTrack = array_merge($targetTermsToTrack, $this->gidTermsTarget[$gid]);
            }
        }
        
        if(!empty($sourceTermsToTrack) || !empty($targetTermsToTrack)) {
            $this->transitInfoString .= '; '.$this->translate->_('QuellTerme').': '.  join(', ', $sourceTermsToTrack).'; '.$this->translate->_('ZielTerme').': '.  join(', ', $targetTermsToTrack).';';
        }
    }
    
    /**
     * returns the term group of one mid and one language
     * @param string $mid
     * @return array empty array if nothing found
     */
    protected function getTermAndGroupIdToMid($mid) {
        $res = $this->termModel->getTermAndGroupIdToMid($mid, $this->task->getTaskGuid());
        if(!empty($res)) {
            return $res;
        }
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        //Error Info: Reason can be a wrong given target language, Fix: reimport with correct target languages according to TBX
        $msg = 'term has not been found in Database, which should be there (Wrong target language given?). TaskGuid: '.$taskGuid;
        $msg .= '; Mid: '.$mid.'; Export continues. '.__FILE__.': '.__LINE__;
        $log->logError($msg);
        return array('groupId' => 0);
    }
    
    /**
     * stores trans[Not]Found counts for the the source terms
     * @param array $termInfo
     */
    protected function countSourceGroupUsage(array $termInfo) {
        $mid = $termInfo['mid'];
        $res = $this->getTermAndGroupIdToMid($mid);
        $gid = $res['groupId'];
        if(empty($gid)) {
            return;
        }
        if(empty($this->gidTermsSource[$gid])) {
            $this->gidTermsSource[$gid] = array();
        }
        $this->gidTermsSource[$gid][$mid] = $res['term'];
                 
        $transFound = in_array('transFound', $termInfo['classes']);
        $transNotFound = in_array('transNotFound', $termInfo['classes']);
        if(empty($this->sourceGidsStatCount[$gid])) {
            $this->sourceGidsStatCount[$gid] = array(
                    'transFound' => 0,
                    'transNotFound' => 0,
            );
        }
        if($transFound) {
            $this->sourceGidsStatCount[$gid]['transFound']++;
        }
        if($transNotFound) {
            $this->sourceGidsStatCount[$gid]['transNotFound']++;
        }
    }
    
    /**
     * stores trans[Not]Found counts for the the source terms
     * @param string $target
     * @param string $mid
     */
    protected function countTargetGroupUsage($target, $mid) {
        $res = $this->getTermAndGroupIdToMid($mid);
        $gid = $res['groupId'];
        if(empty($gid)) {
            return;
        }
        if($target == self::TARGET_TYPE_EDITED) {
            if(empty($this->gidTermsTarget[$gid])) {
                $this->gidTermsTarget[$gid] = array();
            }
            $this->gidTermsTarget[$gid][$mid] = $res['term'];
        }
        if(!isset($this->targetGidsCount[$target][$gid])){
            $this->targetGidsCount[$target][$gid] = 0;
        }
        $this->targetGidsCount[$target][$gid]++;
    }
    
    /**
     * This methods logs the case if a source term is tagged as termNotFound,
     *   but an associated target term exists and vice versa
     *   this is done by comparing the GID transFound counts in source and in target edited 
     */
    protected function logFoundMismatch() {
        foreach($this->sourceGidsStatCount as $gid => $stat){
            $foundInSource = empty($stat['transFound']) ? 0 : $stat['transFound'];
            $foundInTarget = empty($this->targetGidsCount[self::TARGET_TYPE_EDITED][$gid]) ? 0 : $this->targetGidsCount[self::TARGET_TYPE_EDITED][$gid];
            if($foundInSource !== $foundInTarget){
                $log = ZfExtended_Factory::get('ZfExtended_Log');
                /* @var $log ZfExtended_Log */
                $msg = 'Count of transFound source terms with gid '.$gid.' is '.$foundInSource.' but count in target edited is '.$foundInTarget;
                if($this->debug) {
                    $log->logError($msg);
                    $msg .= ' Source Terms: '.print_r($this->gidTermsSource[$gid],1);
                    $msg .= ' Target Terms: '.print_r($this->gidTermsTarget[$gid],1);
                    $msg .= ' Segment: '.print_r($this->segment->getDataObject(),1)."\n in ".__FILE__.': '.__LINE__;
                    error_log($msg);
                } else {
                    $msg .= ' These values should be equal. Enable level 2 debugging for transit plugin and reexport to get more infos in error log.';
                    $log->logError($msg);
                }
            }
        }
    }
}