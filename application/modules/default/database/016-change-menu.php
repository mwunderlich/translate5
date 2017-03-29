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
  README:
  Changes the collation for segment text data for issue: 
    BEOSPHERE-64: Error if Reference file has same name as original file
 */
set_time_limit(0);


/* @var $this ZfExtended_Models_Installer_DbUpdater */

//$this->doNotSavePhpForDebugging = false;

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$config = Zend_Registry::get('config');
$db = Zend_Db::factory($config->resources->db);
$db->query("update Zf_configuration set value = ? where name = 'runtimeOptions.content.mainMenu' and Zf_configuration.value = Zf_configuration.default", '[{"/":"Welcome"},{"/index/mission":"Mission"},{"/login":"Try it online!"},{"/index/usage":"Usage"},  {"/index/install":"Installation"},{"/index/join":"Join the community"}, {"/index/source":"Source"}, {"/index/testimonials":"Testimonials"}, {"/index/newsletter":"Newsletter"}]');
$db->query("update Zf_configuration set Zf_configuration.default = ? where name = 'runtimeOptions.content.mainMenu'", '[{"/":"Welcome"},{"/index/mission":"Mission"},{"/login":"Try it online!"},{"/index/usage":"Usage"},  {"/index/install":"Installation"},{"/index/join":"Join the community"}, {"/index/source":"Source"}, {"/index/testimonials":"Testimonials"}, {"/index/newsletter":"Newsletter"}]');