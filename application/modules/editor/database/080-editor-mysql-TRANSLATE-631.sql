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

INSERT INTO Zf_configuration (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`)
  SELECT REPLACE(name, '407', '600') name, `confirmed`, `module`, `category`, REPLACE(`value`, '4.0.7', '6.0.0') `value`, REPLACE(`default`, '4.0.7', '6.0.0') `default`, `defaults`, `type`, `description`
  FROM Zf_configuration 
  WHERE `name` = 'runtimeOptions.extJs.basepath.407';

UPDATE Zf_configuration SET `default` = '/build/classic/theme-classic/resources/theme-classic-all.css' WHERE name = 'runtimeOptions.extJs.cssFile';

UPDATE Zf_configuration SET `value` = '/build/classic/theme-classic/resources/theme-classic-all.css' 
WHERE name = 'runtimeOptions.extJs.cssFile' AND `value` = '/resources/css/ext-all.css';

