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

/**
 * Service-ServerCommunication Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Service_ServerCommunication {
    
    // REQUIRED FIELDS:
    // *****************************************************
    public $tbxFile = NULL;
    public $sourceLang = NULL;
    public $targetLang = NULL;
    public $segments = NULL;
    /*
    {
        "id": "123",
        "field": "target",
        "source": "SOURCE TEXT",
        "target": "TARGET TEXT"
    },
    { ... MORE SEGMENTS ... }
    ],
    */
    
    /**
     * If $task is sumbitted, ServerCommunication is initialized with all required fields,
     * so after that all there has to be done is addSegment()
     * 
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task = NULL) {
        $config = Zend_Registry::get('config');
        $taggerConfig = $config->runtimeOptions->termTagger;
        $this->debug = (integer)$taggerConfig->debug;
        $this->fuzzy = (integer)$taggerConfig->fuzzy;
        $this->stemmed = (integer)$taggerConfig->stemmed;
        $this->fuzzyPercent = (integer)$taggerConfig->fuzzyPercent;
        $this->maxWordLengthSearch = (integer)$taggerConfig->maxWordLengthSearch;
        $this->minFuzzyStartLength = (integer)$taggerConfig->minFuzzyStartLength;
        $this->minFuzzyStringLength = (integer)$taggerConfig->minFuzzyStringLength;
        
        $this->targetStringMatch = 0;
        if (!$task) {
            return;
        }
        
        $this->tbxFile = $task->meta()->getTbxHash();
        
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $langModel->load($task->getSourceLang());
        $this->sourceLang = $langModel->getRfc5646();
        $langModel->load($task->getTargetLang());
        $this->targetLang = $langModel->getRfc5646();
        $this->targetStringMatch = (int) in_array($this->targetLang, $taggerConfig->targetStringMatch->toArray(), true);
    } 
    
    /**
     * Adds a segment to the server-communication.
     * 
     * @param string $id
     * @param string $field
     * @param string $source
     * @param string $target
     */
    public function addSegment ($id, $field = 'target',  $source, $target) {
        $segment = new stdClass();
        $segment->id = (string) $id;
        $segment->field = $field;
        $segment->source = $source;
        $segment->target = $target;
        
        $this->segments[] = $segment;
    }
}