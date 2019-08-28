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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
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
 */
class editor_Models_Import_FileParser_Sdlxliff_TransunitParser {
    /**
     * The collected mrk source tags of one transunit
     * @var array
     */
    protected $sourceEmptyMrkTags = [];
    
    /**
     * The collected mrk source content of one transunit
     * @var array
     */
    protected $sourceMrkContent = [];
    
    /**
     * The collected mrk target content of one transunit
     * @var array
     */
    protected $targetMrkContent = [];
    
    /**
     * The collected mrk target position indizes in the transunit
     * @var array
     */
    protected $targetMrkChunkIndex = [];
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser = [];
    
    /**
     * Marks if the target was empty
     * @var boolean
     */
    protected $wasEmptyTarget = false;
    
    /**
     * counts the other content chunks
     * @var boolean
     */
    protected $countOtherContent = 0;
    
    /**
     * collected comment references of one segment
     * @var array
     */
    protected $comments = [];
    
    /**
     * collected comments one transUnit
     * @var array
     */
    protected $unitComments = [];
    
    public function __construct() {
        $this->xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
    }
    
    protected function init() {
        $this->wasEmptyTarget = false;
        $this->sourceEmptyMrkTags = [];
        $this->sourceMrkContent = [];
        $this->targetMrkContent = [];
        $this->targetMrkChunkIndex = [];
        $this->comments = [];
        $this->unitComments = [];
        $this->countOtherContent = 0;
    }
    
    protected function initMrkHandler() {
        $this->xmlparser->registerElement('trans-unit > target mrk[mtype=x-sdl-comment]', function($tag, $attr, $key){
            //we have to remove the comment mrks, otherwise they are translated to internal reference internal tags, 
            // which then mess up the TM
            $this->xmlparser->replaceChunk($key, '');
            $commentId = $this->xmlparser->getAttribute($attr, 'sdl:cid');
            // we reuse the attributes array and add a text container for the selected content there:
            $this->comments[$commentId] = [];
        }, function($tag, $key, $opener){
            $this->xmlparser->replaceChunk($key, ''); 
        });
        
        $this->xmlparser->registerOther(function($other, $key) {
            if(empty($other)) {
                return;
            }
            // we count the chunks of othercontent inside the mtype="seg" mrk.
            if($this->xmlparser->getParent('target mrk[mtype=seg]')) {
                $this->countOtherContent++;
            }
            
            //mrk[mtype=x-sdl-comment] can be nested
            $parents = $this->xmlparser->getParents('target mrk[mtype=x-sdl-comment]');
            foreach($parents as $parent) {
                $commentId = $this->xmlparser->getAttribute($parent['attributes'], 'sdl:cid');
                $this->comments[$commentId][] = $other;
            }
        });
        
        //Start segment mrk mtype="seg" handler
        $this->xmlparser->registerElement('trans-unit > target mrk[mtype=seg]', null, function($tag, $key, $opener){
            //reset the other content counter when we enter a segment
            $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid');
            $this->targetMrkChunkIndex[$mid] = [$opener['openerKey'], $key];
            $this->targetMrkContent[$mid] = $this->xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
            foreach($this->comments as $key => $comment) {
                //we have to find out if the comment was for the whole segment or only a part of it
                if(count($comment) == $this->countOtherContent) {
                    //we set the comment to true, that means comment on whole segment not only some word(s)
                    $this->comments[$key] = true; 
                }
            }
            $this->unitComments[$mid] = $this->comments;
            $this->comments = [];
            $this->countOtherContent = 0;
        });
        
        //end segment mrk mtype="seg" handler
        $this->xmlparser->registerElement('trans-unit > seg-source mrk[mtype=seg]', null, function($tag, $key, $opener){
            $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid');
            $this->sourceEmptyMrkTags[$mid] = $this->xmlparser->getChunk($opener['openerKey']).$this->xmlparser->getChunk($key);
            $this->sourceMrkContent[$mid] = $this->xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
        });
    }
    
    /**
     * if there is no or an empty target, easiest way to prepare it, 
     *   is by cloning the source content and then ignore the so created content on parsing
     * @param string $transUnit 
     * @return string
     */
    protected function handleEmptyTarget(string $transUnit): string {
        //if there is no target or an empty target we have to insert it
        if(strpos($transUnit, '<target') === false) {
            $transUnit = str_replace('</seg-source>', '</seg-source>'.'<target></target>', $transUnit);
        }
        else{
            //some versions of SDL Studio adds empty <target/> tags which must be converted then to
            $transUnit = preg_replace('#<target[^>]*/>#', '<target></target>', $transUnit);
        }
        //we fill the target with the source content
        return preg_replace_callback('#<target>\s*</target>#', function() use ($transUnit){
            $this->wasEmptyTarget = true;
            //we split the transunit at the seg-source boundaries which gives as 3 elements, we return the one in the middle.
            $source = preg_split('#<[/]{0,1}seg-source[^>]*>#',$transUnit);
            return '<target>'.$source[1].'</target>';
        }, $transUnit);
    }
    
    /**
     * Parse the given SDLXLIFF transunit, gets the needed data and returns the transunit with placeholders
     * @param string $transUnit
     * @return string
     */
    public function parse(string $transUnit, Callable $segmentSaver): string {
        $this->init();
        $transUnit = $this->handleEmptyTarget($transUnit);
        $this->initMrkHandler();

        //parse the trans-unit
        //trigger segment save on the end of an transunit
        $this->xmlparser->registerElement('trans-unit', null, function($tag, $key, $opener) use ($transUnit, $segmentSaver){
            if(empty($this->sourceMrkContent)) {
                //without any source mrk tag we can do nothing
                return;
            }
            //if there were no target mrks, we have to insert them into the skeleton file
            if(empty($this->targetMrkContent)) {
                //add them into the transUnit and in the skeleton file
                $transUnit = str_replace('</target>', join('', $this->sourceEmptyMrkTags).'</target>', $transUnit);
            }
            //exception if source and target segment count does not match
            elseif(count($this->sourceMrkContent) !== count($this->targetMrkContent)) {
                throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1009', [
                    'filename' => $this->_fileName,
                    'task' => $this->task,
                    'transunit' => $transUnit
                ]);
            }
            
            //in the old parser, the mid's of source and target mrks were not compared, so we do not that here either:
            $mrkMids = array_keys($this->sourceMrkContent);
            $this->sourceMrkContent = array_values($this->sourceMrkContent);
            $this->targetMrkContent = array_values($this->targetMrkContent);
            $this->targetMrkChunkIndex = array_values($this->targetMrkChunkIndex);
            
            //we loop over the found mrk MIDs and save the according content and get the placeholder
            foreach($mrkMids as $idx => $mid) {
                if($this->wasEmptyTarget || empty($this->targetMrkContent[$idx])) {
                    $placeHolder = $segmentSaver($mid, $this->sourceMrkContent[$idx], null, $this->unitComments[$mid] ?? []);
                }
                else {
                    $placeHolder = $segmentSaver($mid, $this->sourceMrkContent[$idx], $this->targetMrkContent[$idx], $this->unitComments[$mid] ?? null);
                }
                
                $startMrk = $this->targetMrkChunkIndex[$idx][0];
                $endMrk = $this->targetMrkChunkIndex[$idx][1];
                
                //
                //add the placeholders to the transunit:
                //
                //empty mrk was a single tag:
                if($startMrk === $endMrk) {
                    //add the end </mrk> tag to the placeholder and replace itself with the new placeholder
                    $placeHolder = preg_replace('#[\s]*/>$#', '>', $this->xmlparser->getChunk($startMrk)).$placeHolder.'</mrk>';
                    $this->xmlparser->replaceChunk($startMrk, $placeHolder);
                }
                //normally a mrk has a start and an end tag
                else {
                    //add the end </mrk> tag to the placeholder and replace itself with the new placeholder
                    $placeHolder .= $this->xmlparser->getChunk($endMrk);
                    $this->xmlparser->replaceChunk($endMrk, $placeHolder);
                    
                    //remove the original content
                    $this->xmlparser->replaceChunk($startMrk + 1, '', $endMrk - $startMrk - 1);
                }
            }
        });
        return $this->xmlparser->parse($transUnit);
    }
}