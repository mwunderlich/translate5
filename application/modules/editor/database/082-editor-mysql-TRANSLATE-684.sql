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

ALTER TABLE `LEK_segments` 
ADD COLUMN `matchRateType` VARCHAR(60) DEFAULT 'import' AFTER `matchRate`;

ALTER TABLE `LEK_segment_history` 
ADD COLUMN `matchRate` INT(11) DEFAULT 0 AFTER `workflowStep`, 
ADD COLUMN `matchRateType` VARCHAR(60) DEFAULT 'import' AFTER `matchRate`;

UPDATE `LEK_segment_history`, `LEK_segments` 
SET `LEK_segment_history`.`matchRate` = `LEK_segments`.`matchRate`
WHERE `LEK_segment_history`.`segmentId` = `LEK_segments`.`id`;