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

UPDATE `LEK_languages` SET `rfc5646` = 'ko' WHERE `LEK_languages`.`langName` = 'Koreanisch';

UPDATE `Zf_configuration` SET `value` = '["zh", "ja", "ko"]' WHERE `Zf_configuration`.`name` = 'runtimeOptions.termTagger.targetStringMatch';
UPDATE `Zf_configuration` SET `default` = '["zh", "ja", "ko"]' WHERE `Zf_configuration`.`name` = 'runtimeOptions.termTagger.targetStringMatch';