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
 * KpiTest imports three simple tasks, sets some KPI-relevant dates, exports some of the tasks,
 * and then checks if the KPIs (Key Performance Indicators) get calculated as expected.
 */
class KpiTest extends \ZfExtended_Test_ApiTestcase {
    
    /**
     * What our tasknames start with (e.g.for creating and filtering tasks).
     * @var string
     */
    protected $taskNameBase = 'API Testing::'.__CLASS__;
    
    /**
     * Settings for the tasks we create and check.
     * @var array
     */
    protected $tasksForKPI = [array('taskNameSuffix' => 'nr1', 'doExport' => true,  'processingTimeInDays' => 100),
                              array('taskNameSuffix' => 'nr2', 'doExport' => false, 'processingTimeInDays' => 102)
    ]; // TODO: add at least a third task for the real testcase
    
    /**
     * Remember the task-ids we created for deleting the tasks at the end
     * taskIds[$taskNameSuffix] = id;
     * @var array 
     */
    protected static $taskIds = [];
    
    /**
     * KPI average processing time: task-property for startdate
     * TODO: With TRANSLATE-1455, change this to: assigned
     * @var string
     */
    protected $taskStartDate = 'orderdate';
    
    /**
     * KPI average processing time: task-property for enddate
     * TODO: With TRANSLATE-1455, change this to: review delivered
     * @var string
     */
    protected $taskEndDate = 'realDeliveryDate';
    
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /**
     * If any task exists already, filtering will be wrong!
     */
    public function testConditions() {
        $filteredTasks = $this->getFilteredTasks();
        $this->assertEquals('0', count($filteredTasks));
    }
    
    /**
     * Create tasks, create values for KPIs, check the KPI-results .
     * @depends testConditions
     */
    public function testKPI() {
        // create the tasks and store their ids
        foreach ($this->tasksForKPI as $task) {
            $this->createTask($task['taskNameSuffix']);
        }
        
        // --- For KPI I: number of exported tasks ---
        foreach ($this->tasksForKPI as $task) {
            if ($task['doExport']) {
                $this->runExcelExport($task['taskNameSuffix']);
            }
        }
        
        // --- For KPI II: average processing time ---
        foreach ($this->tasksForKPI as $task) {
            $interval_spec = 'P'.(string)$task['processingTimeInDays'].'D';
            $this->setTaskProcessingDates($task['taskNameSuffix'], $interval_spec);
        }
        
        // check the KPI-results
        $this->checkKpiResults();
    }
    
    /**
     * Import a task and store the id it got in translate5.
     * @param string $taskNameSuffix
     */
    protected function createTask(string $taskNameSuffix) {
        $task = array(
            'taskName' => $this->taskNameBase.'_'.$taskNameSuffix, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de'
        );
        $this->api()->addImportFile($this->api()->getFile('testcase-de-en.xlf'));
        $this->api()->import($task);
        
        // store task-id for later deleting
        $task = $this->api()->getTask();
        self::$taskIds[$taskNameSuffix] = $task->id;
    }
    
    /**
     * Export a task via API.
     * @param string $taskNameSuffix
     */
    protected function runExcelExport(string $taskNameSuffix) {
        $taskId = self::$taskIds[$taskNameSuffix];
        $this->printUnitTestOutput('runExcelExport: editor/task/'.$taskId.'/excelexport');
        $this->api()->request('editor/task/'.$taskId.'/excelexport');
    }
    
    /**
     * Set the start- and end-date of a task.
     * @param string $taskNameSuffix
     * @param $interval_spec
     */
    protected function setTaskProcessingDates(string $taskNameSuffix, $interval_spec) {
        // We set the endDate to now and the startDate to the given days ago.
        $now = date('Y-m-d H:i:s');
        $endDate = $now;
        $startDate = new DateTime($now);
        $startDate->sub(new DateInterval($interval_spec));
        $startDate = $startDate->format('Y-m-d H:i:s');
        $taskId = self::$taskIds[$taskNameSuffix];
        $this->printUnitTestOutput('setTaskProcessingDates for '.$taskId.': ' . $this->taskStartDate . ' = ' . $startDate .' / ' . $this->taskEndDate . ' = '.$endDate);
        $this->api()->requestJson('editor/task/'.$taskId, 'PUT', array($this->taskStartDate => $startDate, $this->taskEndDate => $endDate));
    }
    
    /**
     * Check if the KPI-result we get from the API matches what we expect.
     */
    protected function checkKpiResults() {
        // Does the number of found tasks match the number of tasks we created?
        $filteredTasks = $this->getFilteredTasks();
        $this->printUnitTestOutput('EXPECTED: ' . count($this->tasksForKPI));
        $this->assertEquals(count($this->tasksForKPI), count($filteredTasks));
        
        // TODO: How to set the filter as form-data????
        $result = $this->api()->requestJson('editor/task/kpi', 'POST', ['filter' => $this->renderTaskGridFilter()]);
        $this->printUnitTestOutput('getKpiResultsFromApi: ' . print_r($result,1));
        
        $statistics = $this->getExpectedKpiStatistics();
        $this->printUnitTestOutput('getExpectedKpiStatistics: ' . print_r($statistics,1));
        
        // averageProcessingTime from API comes with translated unit (e.g. "2 days", "14 Tage"),
        // but these translations are not available here (are they?)
        $search = array("days", "Tage", " ");
        $replace = array("", "", "");
        $result->averageProcessingTime = str_replace($search, $replace, $result->averageProcessingTime);
        
        $this->printUnitTestOutput('averageProcessingTime: result = ' . $result->averageProcessingTime . ' / statistics = ' . $statistics['averageProcessingTime']);
        $this->printUnitTestOutput('excelExportUsage: result = ' . $result->excelExportUsage . ' / statistics = ' . $statistics['excelExportUsage']);
        
        $this->assertEquals($result->averageProcessingTime, $statistics['averageProcessingTime']);
        $this->assertEquals($result->excelExportUsage, $statistics['excelExportUsage']);
    }
    
    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
        foreach (self::$taskIds as $taskId) {
            fwrite(STDOUT, "\n" . '...DELETE: '.$taskId . "\n");
            self::$api->requestJson('editor/task/'.$taskId, 'PUT', array('state' => 'error')); // TODO: this is unfortunately not allowed :( So, how can we can delete tasks without reimport?
            self::$api->requestJson('editor/task/'.$taskId, 'DELETE');
        }
    }
    
    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------
    
    /**
     * Renders the filter for filtering our tasks in the taskGrid.
     * @return string
     */
    protected function renderTaskGridFilter() {
        return '[{"operator":"like","value":"' . $this->taskNameBase . '","property":"taskName"}]';
    }
    
    /**
     * Filter the taskGrid for our tasks only and return the found tasks that match the filtering.
     * @return int
     */
    protected function getFilteredTasks() {
        // taskGrid: apply the filter for our tasks! do NOT use the limit!
        $result = $this->api()->requestJson('editor/task?filter='.urlencode($this->renderTaskGridFilter()), 'GET');
        $this->printUnitTestOutput('editor/task?filter='.$this->renderTaskGridFilter(). ' ===> FOUND: ' . count($result));
        return $result;
    }
    
    /**
     * Get the KPI-values we expect for our tasks.
     * @return array
     */
    protected function getExpectedKpiStatistics() {
        $nrExported = 0;
        $processingTimeInDays = 0;
        $nrTasks = count($this->tasksForKPI);
        foreach ($this->tasksForKPI as $task) {
            if ($task['doExport']) {
                $nrExported++;
            }
            $processingTimeInDays += $task['processingTimeInDays'];
        }
        $statistics = [];
        $statistics['averageProcessingTime'] = (string)round($processingTimeInDays / $nrTasks, 0);
        $statistics['excelExportUsage'] = round((($nrExported / $nrTasks) * 100),2) . '%';
        return $statistics;
    }
    
    /**
     * Output infos during executing the unit-test.
     * @param string $msg
     */
    protected function printUnitTestOutput (string $msg) {
        fwrite(STDOUT, "\n" .$msg . "\n");
    }
}