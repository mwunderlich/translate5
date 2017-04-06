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
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  There is a plugin exception available for use with this release of translate5 for
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

DELETE FROM Zf_acl_rules 
WHERE `module` = 'editor' AND `resource` IN ('editor_plugins_matchresource_resource','editor_plugins_matchresource_taskassoc','editor_plugins_matchresource_tmmt');

DELETE FROM Zf_acl_rules 
WHERE `module` = 'editor' AND `resource` = 'frontend' AND 
`right` IN ('pluginMatchResourceOverview','pluginMatchResourcesAddFilebased','pluginMatchResourceTaskassoc','pluginMatchResourcesAddNonFilebased','pluginMatchResourceMatchQuery','pluginMatchResourceSearchQuery');

DROP TRIGGER `LEK_matchresource_tmmt_versioning`;

DROP TABLE `LEK_matchresource_taskassoc`;
DROP TABLE `LEK_matchresource_tmmt`;

DELETE FROM Zf_configuration WHERE `name` = 'runtimeOptions.plugins.MatchResource.preloadedTranslationSegments';
DELETE FROM Zf_configuration WHERE `name` = 'runtimeOptions.plugins.MatchResource.moses.server';
DELETE FROM Zf_configuration WHERE `name` = 'runtimeOptions.plugins.MatchResource.moses.matchrate';

UPDATE `Zf_configuration` SET `value` = REPLACE(`value`, ',"editor/plugins/resources/matchResource/plugin.css"', '') 
WHERE `name` = 'runtimeOptions.publicAdditions.css';
UPDATE `Zf_configuration` SET `value` = REPLACE(`value`, '"editor/plugins/resources/matchResource/plugin.css",', '') 
WHERE `name` = 'runtimeOptions.publicAdditions.css';
UPDATE `Zf_configuration` SET `value` = REPLACE(`value`, '"editor/plugins/resources/matchResource/plugin.css"', '') 
WHERE `name` = 'runtimeOptions.publicAdditions.css';