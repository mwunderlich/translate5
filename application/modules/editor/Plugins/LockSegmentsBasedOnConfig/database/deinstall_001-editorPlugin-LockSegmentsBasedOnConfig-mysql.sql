-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
-- 			 http://www.gnu.org/licenses/agpl.html
-- 
-- END LICENSE AND COPYRIGHT
-- */

delete from `Zf_worker_dependencies` where 
(
`worker` =  'editor_Plugins_LockSegmentsBasedOnConfig_Worker' AND 
    (`dependency` = 'editor_Plugins_TermTagger_Worker_TermTaggerImport' 
        OR `dependency` = 'editor_Plugins_NoMissingTargetTerminology_Worker' 
        OR `dependency` = 'editor_Plugins_SegmentStatistics_Worker' )
);

delete from `Zf_worker_dependencies` where 
(
`worker` =  'editor_Models_Import_Worker_SetTaskToOpen' AND 
    (`dependency` = 'editor_Plugins_LockSegmentsBasedOnConfig_Worker' )
);
