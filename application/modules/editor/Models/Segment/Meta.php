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

/**
 * Entity Model for segment meta data
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getSegmentId() getSegmentId()
 * @method void setSegmentId() setSegmentId(integer $id)
 * @method string getTransunitId() getTransunitId()
 * @method void setTransunitId() setTransunitId(integer $id)
 * @method string getSiblingData() getSiblingData()
 */
class editor_Models_Segment_Meta extends ZfExtended_Models_Entity_MetaAbstract {
    protected $dbInstanceClass = 'editor_Models_Db_SegmentMeta';
    
    public function loadBySegmentId($id) {
        return $this->loadRow('segmentId = ?', $id);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_MetaAbstract::initEmptyRowset()
     */
    public function initEmptyRowset(){
        //currently not implemented for segment meta, see task meta for usage and what to implement
        // for segments meta add also segment id to initial row set
    }
    
    /**
     * Sets the siblingData field from the given segment instance
     * @param editor_Models_Segment $segment
     */
    public function setSiblingData(editor_Models_Segment $segment) {
        $data = new stdClass();
        $data->nr = $segment->getSegmentNrInTask();
        $data->length = [];
        $editables = $segment->getEditableFieldData();
        foreach($editables as $field => $value){
            $data->length[$field] = $segment->textLength($value);
        }
        $this->__call(__FUNCTION__, [json_encode($data)]);
    }
}