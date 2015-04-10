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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Enthält Methoden zum Fileparsing für den Export
 */
abstract class editor_Models_Export_FileParser {
    /**
     * @var string
     */
    protected $_exportFile = NULL;
    /**
     * @var string
     */
    protected $_skeletonFile = NULL;
    /**
     * @var integer
     */
    protected $_fileId = NULL;
    /**
     * @var editor_Models_Segment aktuell bearbeitetes Segment
     */
    protected $_segmentEntity = NULL;
    /**
     * contains a limited amount of loaded segments
     * @var array
     */
    protected $segmentCache = array();
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = NULL;
    /**
     * @var object 
     */
    protected $_difftagger = NULL;
    /**
     * @var boolean wether or not to include a diff about the changes in the exported segments
     *
     */
    protected $_diff= false;
    /**
     * @var editor_Models_Task current task
     */
    protected $_task;
    /**
     * @var Zend_Config
     */
    protected $config;
    /**
     *
     * @var string path including filename, on which the exported file will be saved
     */
    protected $path;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * Disables the MQM Export if needed
     * @var boolean
     */
    protected $disableMqmExport = false;
    
    /**
     * 
     * @param integer $fileId
     * @param boolean $diff
     * @param editor_Models_Task $task
     * @param string $path 
     * @throws Zend_Exception
     */
    public function __construct(integer $fileId,boolean $diff,editor_Models_Task $task, string $path) {
        if(is_null($this->_classNameDifftagger)){
            throw new Zend_Exception('$this->_classNameDifftagger muss in der Child-Klasse definiert sein.');
        }
        $this->_fileId = $fileId;
        $this->_diffTagger = ZfExtended_Factory::get($this->_classNameDifftagger);
        $this->_diff = $diff;
        $this->_task = $task;
        $this->path = $path;
        $this->config = Zend_Registry::get('config');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     * 
     * @return string file
     */
    public function getFile() {
        $this->getSkeleton();
        $this->parse();
        $this->convertEncoding();
        return $this->_exportFile;
    }
    
    public function saveFile() {
        file_put_contents($this->path, $this->getFile());
    }
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse() {
        $file = preg_split('#<lekTargetSeg([^>]+)/>#', $this->_skeletonFile, null, PREG_SPLIT_DELIM_CAPTURE);

        //reusable exception creation
        $exception = function($val) {
            $e  = 'Error in Export-Fileparsing. instead of a id="INT" and a optional ';
            $e .= 'field="STRING" attribute the following content was extracted: ' . $val;
            return new Zend_Exception($e);
        };
        
        $count = count($file) - 1;
        for ($i = 1; $i < $count;) {
            $file[$i] = $this->preProcessReplacement($file[$i]);
            if (!preg_match('#^\s*id="([^"]+)"\s*(field="([^"]+)"\s*)?$#', $file[$i], $matches)) {
                throw $exception($file[$i]);
            }
          
            //check $matches[1] for integer (segmentId) if empty throw an exception
            settype($matches[1], 'int');
            if(empty($matches[1])) {
                throw $exception($file[$i]);
            }
          
            //alternate column is optional, use target as default
            if(isset($matches[3])) {
                $field = $matches[3];
            }
            else {
              $field = editor_Models_SegmentField::TYPE_TARGET;
            }
          
            $file[$i] = $this->getSegmentContent($matches[1], $field);
            $i = $i + 2;
        }
        $this->_exportFile = implode('', $file);
    }
    
    /**
     * pre processor for the extracted lekTargetSeg attributes
     * for overwriting purposes only
     * @param string $attributes
     * @return string
     */
    protected function preProcessReplacement($attributes) {
        return $attributes;
    }
    
    /**
     * returns the segment content for the given segmentId and field. Adds optional diff markup, and handles tags.
     * @param integer $segmentId
     * @param string $field fieldname to get the content from
     * @return string
     */
    protected function getSegmentContent($segmentId, $field) {
        $this->_segmentEntity = $segment = $this->getSegment($segmentId);
        
        $edited = (string) $segment->getFieldEdited($field);
        
        $edited = $this->recreateTermTags($edited, $this->shouldTermTaggingBeRemoved());
        $edited = $this->parseSegment($edited);

        if(!$this->_diff){
            return $this->unprotectWhitespace($edited);
        }
        
        $original = (string) $segment->getFieldOriginal($field);
        $original = $this->recreateTermTags($original);
        $original = $this->parseSegment($original);
        
        $diffed = $this->_diffTagger->diffSegment(
                $original, 
                $edited,
                $segment->getTimestamp(),
                $segment->getUserName());
        // unprotectWhitespace must be done after diffing!
        return $this->unprotectWhitespace($diffed);
    }
    
    protected function shouldTermTaggingBeRemoved() {
        $removeTaggingOnExport = $this->config->runtimeOptions->termTagger->removeTaggingOnExport;
        $return = $this->_diff ? $removeTaggingOnExport->diffExport : $removeTaggingOnExport->normalExport;
        return (boolean)$return;
    }
    /**
     * loads the segment to the given Id, caches a limited count of segments internally 
     * to prevent loading again while switching between fields
     * @param integer $segmentId
     * @return editor_Models_Segment
     */
    protected function getSegment($segmentId){
        if(isset($this->segmentCache[$segmentId])) {
            return $this->segmentCache[$segmentId];
        }
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $segment->load($segmentId);
        /* @var $segment editor_Models_Segment */
        $this->segmentCache[$segmentId] = $segment;
        //we keep a max of 50 segments, this should be enough
        if(count($this->segmentCache) > 50) {
            reset($this->segmentCache);
            $firstKey = key($this->segmentCache);
            unset($this->segmentCache[$firstKey]);
        }
        return $segment;
    }
    
    /**
     * creates termMarkup according to xliff-Syntax (<mrk ...) 
     * 
     * converts from:
     * <div class="term admittedTerm transNotFound" id="term_05_1_de_1_00010-0" title="">Hause</div>
     * to:
     * <mrk mtype="x-term-admittedTerm" mid="term_05_1_de_1_00010">Hause</mrk>
     * and removes the information about trans[Not]Found
     * 
     * @param string $segment
     * @param boolean $removeTermTags, default = true
     * @return string $segment
     */
    protected function recreateTermTags($segment, $removeTermTags=true) {
        $segmentArr = preg_split('/<div\s*class="term([^"]+)"\s+id="([^"]+)-\d+"[^>]*>/s', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        
        $cssClassFilter = function($input) {
            return($input !== 'transFound' && $input !== 'transNotFound');
        };
        
        $count = count($segmentArr);
        $closingTag =  '</mrk>';
        if($removeTermTags){
            $closingTag = '';
        }
        for ($i = 1; $i < $count; $i = $i + 3) {
            $tagExpl/* segment aufgespalten an den öffenden Tags */ = explode('<div', $segmentArr[$i + 2]/* segmentteil hinter öffnendem Termtag */);
            $openTagCount = 0;
            $tCount = count($tagExpl);
            for ($j = 0; $j < $tCount; $j++) {
                $numOfClosedDiv = substr_count($tagExpl[$j], '</div>');
                $containsOpeningTag = preg_match('"^ class=\""', $tagExpl[$j]) === 1 || false;
                if ($openTagCount === 0 and
                        (
                        ($containsOpeningTag === true and $numOfClosedDiv > 1)
                        or
                        ($containsOpeningTag === false and $numOfClosedDiv === 1))) {
                    $parts = explode('</div>', $tagExpl[$j]); //der letzte </div> muss der schließende mrk-Tag sein, da ja kein div-Tag innerhalb des Term-Tags mehr geöffnet ist
                    $end = array_pop($parts);
                    $tagExpl[$j] = implode('</div>', $parts) . $closingTag. $end;
                    break; //go to the next termtag, because this one is now closed.
                } elseif (($containsOpeningTag === true and $numOfClosedDiv > 1)
                        or
                        ($containsOpeningTag === false and $numOfClosedDiv === 1)) {
                    $openTagCount--;
                } elseif ($containsOpeningTag and $numOfClosedDiv === 0) {
                    $openTagCount++;
                }
            }
            if(!$removeTermTags){
                $cssClasses = explode(' ', trim($segmentArr[$i]));
                //@todo actually were removing the trans[Not]Found info. 
                //it would be better to set it for source segments by checking the target if the term exists  
                $segmentArr[$i] = join('-', array_filter($cssClasses, $cssClassFilter));
                $segmentArr[$i] = '<mrk mtype="x-term-' . $segmentArr[$i] . '" mid="' . $segmentArr[$i + 1] . '">';
            }
            else{
                $segmentArr[$i] = '';
            }
            $segmentArr[$i] .= implode('<div', $tagExpl);
            unset($segmentArr[$i + 1]);
            unset($segmentArr[$i + 2]);
        }
        return implode('', $segmentArr);
    }
    
    /**
     * converts the QM-Subsegment-Tags to xliff-format
     * 
     * @param string $segment
     * @return string
     */
    protected function convertQmTags2XliffFormat($segment){
        $flags = $this->_task->getQmSubsegmentFlags();
        if(empty($flags)){
            return $segment;
        }
        $split = preg_split('"(<img[^>]+class=\"[^\"]*qmflag[^\"]*\"[^>]*>)"', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($split);
        if($count==1) return $segment;
        $i = 1;
        
        //if disabled we return the segment content without mqm
        if($this->disableMqmExport) {
            for (; $i < $count; $i=$i+2) {//the uneven numbers are the tags
                $split[$i] = ''; //remove mqm tag
            }
            return implode('', $split);
        }
        
        $check = function($type,$content,$input,$empty = true){
            if($empty && $content == ''){
                trigger_error($type.' had been emtpy when extracting from qm-subsegment-tag.',E_USER_ERROR);
            }
            if($content == $input){
                #trigger_error($type.' could not be extracted from qm-subsegment-tag.',E_USER_ERROR);
            }
        };
        
        $extract = function($type,$numeric = false,$empty = true)use (&$split,&$i,$check){
            $a = '[^\"]*';
            if($numeric)$a = '\d+';
            $content = preg_replace('".*'.$type.'=\"('.$a.')\".*"', '\\1', $split[$i]);
            $check($type,$content,$split[$i],$empty);
            return $content;
        };
        
        $issues = $this->_task->getQmSubsegmentIssuesFlat();
        $user = $this->_segmentEntity->getUserName();
        
        for (; $i < $count; $i=$i+2) {//the uneven numbers are the tags
            $id = $extract('data-seq',true);
            $class = $extract('class');
            $open = (boolean)preg_match('"^(open .*)|(.* open)|(.* open .*)$"', $class);
            
            if($open){
                $comment = $extract('data-comment',false,false);
                $severity = preg_replace('"^\s*([^ ]+) .*$"', '\\1', $class);
                $check('severity',$severity,$class);
                $issueId = preg_replace('"^.*qmflag-(\d+).*$"', '\\1', $class);
                $check('issueId',$issueId,$class);
                $issue = $issues[$issueId];
                
                $split[$i] = '<mqm:startIssue type="'.$issue.'" severity="'.
                        $severity.'" note="'.$comment.'" agent="'.$user.
                        '" id="'.$id.'"/>';
            }
            else{
                $split[$i] = '<mqm:endIssue id="'.$id.'"/>';
            }
        }
        return implode('', $split);
    }

    /**
     * sets $this->_skeletonFile
     */
    protected function getSkeleton() {
        $skel = ZfExtended_Factory::get('editor_Models_Skeletonfile');
        $skel->loadRow('fileId = ?', $this->_fileId);
        $this->_skeletonFile = $skel->getFile();
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     * @param string $segment
     * @return string $segment 
     */
    protected function parseSegment($segment) {
        $segmentArr = preg_split($this->config->runtimeOptions->editor->export->regexInternalTags, $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($segmentArr);
        for ($i = 1; $i < $count;) {
            $j = $i + 2;
            // detect if single-tag is regex-tag, if not capsule result with brackets (= normal behavior)
            $isRegexTag = $segmentArr[$i+2] == "regex";
            $segmentArr[$i] = pack('H*', $segmentArr[$i + 1]);
            if (!$isRegexTag) {
                //the following search and replace is needed for TRANSLATE-464
                //backwards compatibility of already imported tasks
                $search = array('hardReturn /','softReturn /','macReturn /');
                $replace = array('hardReturn/','softReturn/','macReturn/');
                $segmentArr[$i] = str_replace($search, $replace, $segmentArr[$i]);
                $segmentArr[$i] = '<' . $segmentArr[$i] .'>';
            }
            
            unset($segmentArr[$j]);
            unset($segmentArr[$i + 1]);
            $i = $i + 4;
        }
        return implode('', $segmentArr);
    }
    
    /**
     * - converts $this->_exportFile back to the original encoding registered in the LEK_files
     */
    protected function convertEncoding(){
        $file = ZfExtended_Factory::get('editor_Models_File');
        $file->load($this->_fileId);
        $enc = $file->getEncoding();
        if(is_null($enc) || $enc === '' || strtolower($enc) === 'utf-8')return;
        $this->_exportFile = iconv('utf-8', $enc, $this->_exportFile);
    }
    
    /**
     * Exports a single segment content, without MQM support!
     * @param string $segment
     * @return string
     */
    public function exportSingleSegmentContent($segment) {
        $this->disableMqmExport = true;
        return $this->recreateTermTags($this->parseSegment($segment));
    }
    
    /**
     * unprotects tag protected whitespace inside the given segment content
     * keep attention to the different invocation points for this method!
     * @param string $content
     * @return string
     */
    protected function unprotectWhitespace($content) {
        $search = array(
          '<hardReturn/>',
          '<softReturn/>',
          '<macReturn/>'
        );
        $replace = array(
          "\r\n",  
          "\n",  
          "\r"
        );
        $content = str_replace($search, $replace, $content);
        return preg_replace_callback('"<space ts=\"([A-Fa-f0-9]*)\"/>"', function ($match) {
                    return pack('H*', $match[1]);
                }, $content);
    }
}
