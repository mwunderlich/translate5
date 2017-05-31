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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Contains Methods for Fileparsing on the Import
 * 
 * - Child Classes must implement and use the abstract methods
 */
abstract class editor_Models_Import_FileParser {
    /**
     * @var integer Counter of tags used in a segment.
     */
    protected $_tagCount = 1;
    /**
     * @var string
     */
    protected $_origFile = NULL;
    /**
     * @var string
     */
    protected $_skeletonFile = NULL;
    /**
     * @var string
     */
    protected $_fileName = NULL;
    /**
     * @var integer
     */
    protected $_fileId = NULL;
    
    /**
     * array containing all segment data parsed
     * @var [array] 2D array, first level has keys which map to the segment field names. Second Level array must be compliant to editor_Models_Db_SegmentDataRow
     */
    protected $segmentData = array();
    
    /**
     * @var string mid des aktuellen Segments
     */
    protected $_mid = NULL;
    
    /**
     * contains a SegmentAttributes object (value) per mid (key)
     * @var [editor_Models_Import_FileParser_SegmentAttributes]
     */
    protected $segmentAttributes = array();
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var string taskGuid
     */
    protected $_taskGuid = NULL;

    /**
     * Contains a list of processors of the parsed segmentdata
     * @var [editor_Models_Import_SegmentProcessor]
     */
    protected $segmentProcessor = array();
    
    /**
     * all files with extensions listet here are converted to utf8. For details see method convert2utf8
     * @var array
     */
    protected $_convert2utf8 = array('csv');
    /**
     * @var string $path path to the file in the encoding of the filesystem (runtimeOptions.fileSystemEncoding)
     */
    protected $_path;
    
    /**
     * Auto State Definer
     * @var editor_Models_Segment_AutoStates
     */
    protected $autoStates;
    
    /**
     * MatchRateType calculator / converter
     * @var editor_Models_Segment_MatchRateType
     */
    protected $matchRateType;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * @param string $path pfad zur Datei in der Kodierung des Filesystems (also runtimeOptions.fileSystemEncoding)
     * @param string $fileName Dateiname utf-8 kodiert
     * @param integer $fileId
     * @param editor_Models_Task $task
     */
    public function __construct(string $path, string $fileName,integer $fileId, editor_Models_Task $task){
        $this->config = Zend_Registry::get('config');
        $this->_origFile = file_get_contents($path);
        $this->_path = $path;
        $this->_fileName = $fileName;
        $this->_fileId = $fileId;
        $this->task = $task;
        $this->_taskGuid = $task->getTaskGuid();
        $this->autoStates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        $this->matchRateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        $this->handleEncoding();
    }
    
    /**
     * Prototyp-function for getting word-count while import process.
     * This function is (or is not) overwritten by typ-specific import-parser 
     */
    public function getWordCount()
    {
        return false;
    }
    
    /**
     * Das Leerzeichen (U+0020)
     * Schützt Zeichenketten, die im sdlxliff enthalten sind und aus einer
     * Unicode Private Use Area oder bestimmten schutzwürdigen Whitespaces oder
     * von mssql nicht verkrafteten Zeichen stammen mit einem Tag
     *
     */
    protected function protectUnicodeSpecialChars() {
        $this->_origFileUnicodeProtected = preg_replace_callback(
                array('"\p{Co}"u', //Alle private use chars
            '"\x{2028}"u', //Hex UTF-8 bytes or codepoint 	E2 80 A8//schutzbedürftiger Whitespace + von mssql nicht vertragen
            '"\x{2029}"u', //Hex UTF-8 bytes 	E2 80 A9//schutzbedürftiger Whitespace + von mssql nicht vertragen
            //we do not escape that any more - mssql not in use '"\x{201E}"u', //Hex UTF-8 bytes 	E2 80 9E //von mssql nicht vertragen
            //we do not escape that any more - mssql not in use '"\x{201C}"u' //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
            ), 
                function ($match) {
                    return '<unicodePrivateUseArea ts="' . implode(',', unpack('H*', $match[0])) . '"/>';
                }, $this->_origFile);
        $this->_origFileUnicodeSpecialCharsRemoved = preg_replace_callback(
                array('"\p{Co}"u', //Alle private use chars
            '"\x{2028}"u', //Hex UTF-8 bytes 	E2 80 A8//schutzbedürftiger Whitespace + von mssql nicht vertragen
            '"\x{2029}"u', //Hex UTF-8 bytes 	E2 80 A9//schutzbedürftiger Whitespace + von mssql nicht vertragen
            //we do not escape that any more - mssql not in use '"\x{201E}"u', //Hex UTF-8 bytes 	E2 80 9E //von mssql nicht vertragen
            //we do not escape that any more - mssql not in use '"\x{201C}"u' //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
            ), 
                function ($match) {
                    return '';
                }, $this->_origFile);
    }
    
    public function addSegmentProcessor(editor_Models_Import_SegmentProcessor $proc){
        $this->segmentProcessor[] = $proc;
    }
    
    /**
     * set the shared instance of the segmentFieldManager
     * @param $sfm editor_Models_SegmentFieldManager
     */
    public function setSegmentFieldManager(editor_Models_SegmentFieldManager $sfm) {
        $this->segmentFieldManager = $sfm;
        $this->initDefaultSegmentFields();
    }
    
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @param integer $count optional, variable passed by reference stores the replacement count
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment, &$count = 0) {
        $split = preg_split('#(<[^>]+>)#', $segment, null, PREG_SPLIT_DELIM_CAPTURE);
        $search = array(
          "\r\n",  
          "\n",  
          "\r"
        );
        $replace = array(
          '<hardReturn/>',
          '<softReturn/>',
          '<macReturn/>'
        );
        $i = 0;
        foreach($split as $idx => $chunk) {
            if($i++ % 2 === 1 || strlen($chunk) == 0) {
                //ignore found tags in the content or empty chunks
                continue; 
            }
            //replace only on real text
            $chunk = str_replace($search, $replace, $chunk, $returnCount);
            $count += $returnCount;
            
            $replacer = function ($match) {
                            return ' <space ts="' . implode(',', unpack('H*', $match[1])) . '"/>';
                        };
            
            //protect multispaces
            $split[$idx] = preg_replace_callback('" ( +)"', $replacer, $chunk, -1, $spaceCount);
            $count += $spaceCount;
        }
        return join($split);
    }
    
    /**
     * encodes special chars to entities for display in title-Attributs and text of tags in the segments
     * because studio sometimes writes tags in the description of tags (i.e. in locked tags)
     *
     * @param string text
     * @return string text
     */
    protected function encodeTagsForDisplay($text) {
        return str_replace(array('"',"'",'<','>'),array('&quot;','&#39;','&lt;','&gt;'),$text);
    }
    
    /**
     * returns the internally used SegmentFieldManager
     * @return editor_Models_SegmentFieldManager
     */
    public function getSegmentFieldManager() {
        return $this->segmentFieldManager;
    }
    
    /**
     * Gibt den Inhalt das erzeugte Skeleton File zurück 
     * @return string
     */
    public function getSkeletonFile() {
        return $this->_skeletonFile;
    }
    
    /**
     * does the fileparsing
     * - calls extractSegment, parseSegmentAttributes
     */
    abstract protected function parse();

    /**
     * initiates the default fields source and target, should be overwritten if fields differ.
     */
    protected function initDefaultSegmentFields() {
        $sfm = $this->segmentFieldManager;
        $sourceEdit = (boolean) $this->task->getEnableSourceEditing();
        $sfm->addField($sfm::LABEL_SOURCE, editor_Models_SegmentField::TYPE_SOURCE, $sourceEdit);
        $sfm->addField($sfm::LABEL_TARGET, editor_Models_SegmentField::TYPE_TARGET);
    }
    
    /**
     * Does the fileparsing
     * FIXME replace the pre and post parse handlers with events 
     */
    public function parseFile() {
        foreach($this->segmentProcessor as $p) {
            $p->preParseHandler($this);
        }
        $this->parse();
        foreach($this->segmentProcessor as $p) {
            $p->postParseHandler($this);
        }
    }
    
    /**
     * Speichert das Segment in die Datenbank
     * FIXME replace the post process handlers with events 
     *
     * @param mixed transunit
     * @return integer segmentId
     */
    protected function setAndSaveSegmentValues(){
        $this->setCalculatedSegmentAttributes();
        $result = false;
            
        foreach($this->segmentData as &$field) {
            //preset the md5 field with the plain string
            //the different processors have then the ability to modify it
            //the final segment processor creates then the hash before storing it into the DB
            $field['originalMd5'] = $field['original'];
        }
        
        foreach($this->segmentProcessor as $p) {
            /* @var $p editor_Models_Import_SegmentProcessor */
            $r = $p->process($this);
            if($r !== false) {
                $result = $r;
            }
        }
        foreach($this->segmentProcessor as $p) {
            $p->postProcessHandler($this, $result);
        }
        return $result;
    }

    /**
     * creates a new (or returns the already existing) segment attributes object, and stores it internally to the given mid
     * @param string $forMid
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    protected function createSegmentAttributes($forMid) {
        if(isset($this->segmentAttributes[$forMid])) {
            return $this->segmentAttributes[$forMid];
        }
        $segAttr = ZfExtended_Factory::get('editor_Models_Import_FileParser_SegmentAttributes');
        /* @var $segAttr editor_Models_Import_FileParser_SegmentAttributes */
        return $this->segmentAttributes[$forMid] = $segAttr;
    }
    
    /**
     * returns a already existing segment attributes object
     * @param string $forMid
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    public function getSegmentAttributes($forMid) {
        //just call the create call, since it does what we want, 
        //  but the method name is misleading here
        return $this->createSegmentAttributes($forMid);
    }
    
    /**
     * returns a placeholder for reexport the edited content
     * @param integer $segmentId
     * @param string $name
     */
    protected function getFieldPlaceholder($segmentId, $name) {
        return '<lekTargetSeg id="'.$segmentId.'" field="'.$name.'" />';
    }
    
    /**
     * checks the encoding of the file and saves the encoding to the file-table
     * - only saves encoding for formats listed in this->_convert2utf8
     * @triggers error if encoding could not be detected; in this case does not save anything to db
     */
    protected function handleEncoding() {
        $convert = false;
        foreach ($this->_convert2utf8 as $format){
            if(preg_match('"\.'.$format.'$"i', $this->_fileName)===1){
                $convert = TRUE;
            }
        }
        if(!$convert)return;
        $enc = $this->checkAndConvert2utf8();
        if(!$enc){
            trigger_error('The encoding of the file "'.
                    $this->_fileName.
                    '" is none of the encodings utf-8, iso-8859-1 and win-1252.',
                    E_USER_ERROR);
        }
        $file = ZfExtended_Factory::get('editor_Models_File');
        $file->load($this->_fileId);
        $file->setEncoding($enc);
        $file->save();
    }

    /**
     * converts all fileformats listed in $this->_convert2utf8 to utf8, if possible
     * - calls saveEncoding, catches exeption
     * - converts Latin1 and windows-1252-encoded file to utf-8, even if part of
     *   file is in utf8 already. In any other case a user_error is triggered
     * - if exception is caught, saveEncoding is called after conversion
     *   again and this time a caught exception triggers a user_error
     * @return string encoding | false if encoding is none of utf-8, 'iso-8859-1', 'windows-1251'
     */
    protected function checkAndConvert2utf8() {
        if(mb_detect_encoding($this->_origFile, 'UTF-8', true))return 'UTF-8';
        
        $list = array('iso-8859-1', 'windows-1251');
     
        foreach ($list as $item) {
            $sample = iconv($item, $item, $this->_origFile);
            if (md5($sample) == md5($this->_origFile)) { 
                 $this->_origFile = iconv($item, 'UTF-8', $this->_origFile);
                 return $item;
            }
        }
        return false;
    }

    /**
     * parses the current segment data for segment attributes, must call createSegmentAttributes therefore
     * @param mixed transunit
     */
    abstract protected function parseSegmentAttributes($transunit);
    
    /**
     * checks and sets the given MID internally
     * Because of DB reasons we only accept a 60chars long MID. If the given value was longer we trigger an error.
     * @param string $mid
     */
    protected function setMid($mid) {
        if(mb_strlen($mid) > 60) {
            trigger_error('Given MID was to long (max 60 chars), MID: '.$mid, E_USER_ERROR);
        }
        $this->_mid = $mid;
    }
    
    /**
     * calculates and sets segment attributes needed by us, this info doesnt exist directly in the segment. 
     * These are currently: pretrans, editable, autoStateId
     * Parameters are given by the current segment
     */
    protected function setCalculatedSegmentAttributes() {
        $attributes = $this->getSegmentAttributes($this->_mid);
        
        $isAutoprop = $attributes->autopropagated;
        $isLocked = $attributes->locked && (bool) $this->task->getLockLocked();
       
        $isFullMatch = ($attributes->matchRate === 100);
        $isTranslated = $this->isTranslated();
        
        //calculate isEditable only if it was not explicitly set
        if(!isset($attributes->editable)) {
            $isEditable  = (!$isFullMatch || (bool) $this->task->getEdit100PercentMatch() || $isAutoprop) && !$isLocked;
            $attributes->editable = $isEditable;
        }
        $attributes->pretrans = $isFullMatch && !$isAutoprop;
        $attributes->autoStateId = $this->autoStates->calculateImportState($attributes->editable, $isTranslated);
        $attributes->isTranslated = $isTranslated;
        
        //if there was a matchRateType from the imported segment, then the original value was stored
        $attributes->matchRateType = $this->matchRateType->parseImport($attributes, $this->_mid);
    }
    
    /**
     * returns true if at least one target has a translation set
     */
    protected function isTranslated() {
        foreach($this->segmentData as $name => $data) {
            $field = $this->segmentFieldManager->getByName($name);
            if($field->type !== editor_Models_SegmentField::TYPE_TARGET) {
                continue;
            }
            if(!empty($data['original'])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Extrahiert aus einer trans-unit Quell-
     * und Zielsegmente
     *
     * - speichert die Segmente in der Datenbank
     * @param mixed $transUnit
     * @return mixed $transUnit
     */
    abstract protected function extractSegment($transUnit);
    
    /**
     * Extrahiert aus einem Segment (bereits ohne umschließende Tags) die Tags
     *
     * - speichert die Tags in der Datenbank
     *
     * @param mixed $segment
     * @param boolean isSource
     * @return mixed $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    abstract protected function parseSegment($segment,$isSource);

    /**
     * Gibt die MID des aktuellen Segments zurück
     * @return string
     */
    public function getMid(){
        return $this->_mid;
    }

    /**
     * returns a reference to the array with all parsed data fields
     * The reference enables the ability to manipulate the parsed data in the segmentprocessors (see MqmParser)
     * @see editor_Models_Import_SegmentProcessor_MqmParser
     */
    public function & getFieldContents() {
        return $this->segmentData;
    }
}
