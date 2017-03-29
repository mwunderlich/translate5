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
 * overrides the segment filters, so that segment content is filtered with ignored case although we use bin collation in DB
 */
class editor_Models_Filter_SegmentSpecific extends ZfExtended_Models_Filter_ExtJs6 {

    /**
     * internal saved segment field names
     * @var array
     */
    protected $segmentFields = null;
    
    /**
     * sets the fields which should be filtered lowercase
     * @param array $fields
     */
    public function setSegmentFields(array $fields) {
        $this->segmentFields = $fields;
    }
    
    /**
     * @param string $field
     * @param string $value
     */
    protected function applyString($field, $value) {
        if(is_null($this->segmentFields)) {
            throw new ZfExtended_Exception(__CLASS__.'::SegmentFields not initialized!');
        }
        
        if(in_array($field, $this->segmentFields)) {
            $this->where(' lower('.$field.') like lower(?)', '%'.$value.'%');
        }
        else {
            parent::applyString($field, $value);
        }
  }
    
}