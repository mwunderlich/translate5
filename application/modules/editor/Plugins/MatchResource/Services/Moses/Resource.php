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

class editor_Plugins_MatchResource_Services_Moses_Resource extends editor_Plugins_MatchResource_Models_Resource {
    public function __construct(string $id, string $name, string $url) {
        parent::__construct($id, $name, $url);
        $this->filebased = false; //forced to be no filebased
        $this->searchable = false; //forced to be non searchable
        $this->writable = false; //forced to be non writeable
        $this->type = editor_Models_Segment_MatchRateType::TYPE_MT;
    }
}