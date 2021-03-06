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
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or 
--  plugin-exception.txt in the root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE IF NOT EXISTS `LEK_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dateOfChange` date DEFAULT NULL,
  `jiraNumber` varchar(100) DEFAULT NULL,
  `title` varchar(256) DEFAULT NULL,
  `description` mediumtext,
  `userGroup` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `jiraNumberDate_unique` (`dateOfChange`, `jiraNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `title`, `description`, `userGroup`) VALUES ('2016-09-27', 'TRANSLATE-637', 'Inform users about application changes', 'The application informs users about new features / changes in a separate pop-up window.', '14'),('2016-09-27', 'TRANSLATE-137', 'Introduced a Maintenance Mode', 'The Maintenance Mode provides administrators with the ability to lock the whole application to prevent data loss on system updates.', '14'),('2016-09-27', 'TRANSLATE-680', 'Repetition-Editor now can handle tags in segments', 'Until this update the repetition editor could not handle segments containing tags. This means, these segments have never been found as repetitions. Now repetitions are found regardless of the tags and the tag-content. Just the tag position and number of tags in the segment must be equal in repeated segments.', '14'),('2016-09-27', 'TRANSLATE-612', 'User-Authentication via API', 'The API can now be used user authentication, see http://confluence.translate5.net/display/TAD/Session', '8'),('2016-09-27', 'TRANSLATE-664', 'Integrate separate help area', 'In configuration a URL to own help pages can be configured. The default header of translate5 must be used. See http://confluence.translate5.net/display/CON/Database+based+configuration', '14'),('2016-09-27', 'TRANSLATE-684', 'Introduce match-type column', 'Only for new imported tasks! The match type provided in SDLXLIFF files (like TM-Match, interactive, etc.) is now displayed as own hidden column in the segment grid and shown as icon with a tooltip in the match-rate column.', '14'),('2016-09-27', 'TRANSLATE-644', 'enable editor-only usage in translate5', 'Improved the ability to embed the Translate5 Editor component into an external system, that is used for task and user management.', '8'),('2016-09-27', 'TRANSLATE-718', 'Introduce a config switch to disable comment export', 'With TRANSLATE-707 exporting of comments into SDLXLIFF files was introduced. This can now optionally deactivated by a config switch.', '12'),('2016-09-27', 'TRANSLATE-625', 'Switch Task-Import and -export to work asynchronously', 'Switched import and export completely to asynchronous processing. This means, PMs do not have to wait for an import to finish, before they can proceed with other tasks in the GUI. It also means for admins, that they can now configure in the databse, how many imports are allowed to run at the same time. See runtimeOptions.worker.editor_Models_Import_Worker_SetTaskToOpen.maxParallelWorkers', '12'),('2016-09-27', 'TRANSLATE-621', 'New task status error', 'When there are errors while importing a task (configuration / corrupt data) the task remains in the application with the status „error“.', '12'),('2016-09-27', 'TRANSLATE-646', 'Improved segment content filter to handle special chars', 'Searching / filtering in segment content can deal now with special characters (for example German Umlaute)', '14'),('2016-09-27', 'TRANSLATE-725', 'Fix error when using status column filter in task overview', '', '14'),('2016-09-27', 'TRANSLATE-727', 'Fix error when using language column filters in task overview', '', '14'),('2016-09-27', 'TRANSLATE-728', 'Provide missing column titles in match resource plug-in', 'Several column labels related to the MatchResource plug-in were missing.', '14'),('2016-09-27', 'several', 'Fixing ACL errors related to the plug-in system', 'Since more functionality is provided as plug-ins, some changes / fixes in core ACL system were needed.', '8'),('2016-09-27', 'TRANSLATE-715', 'Fix MQM short cut labels', 'The labelling of the keyboard shortcuts were wrong in the MQM menu.', '14');
