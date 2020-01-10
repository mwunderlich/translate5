<?php

/*
 * START LICENSE AND COPYRIGHT
 *
 * This file is part of translate5
 *
 * Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 *
 * Contact: http://www.MittagQI.com/ / service (ATT) MittagQI.com
 *
 * This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 * as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 * included in the packaging of this file. Please review the following information
 * to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 * http://www.gnu.org/licenses/agpl.html
 *
 * There is a plugin exception available for use with this release of translate5 for
 * translate5: Please see http://www.translate5.net/plugin-exception.txt or
 * plugin-exception.txt in the root folder of translate5.
 *
 * @copyright Marc Mittag, MittagQI - Quality Informatics
 * @author MittagQI - Quality Informatics
 * @license GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 * http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
 *
 * END LICENSE AND COPYRIGHT
 */

/**
 * In order to test if we query the DeepL-Api as intended, we:
 * - create a DeepL-LanguageReource and a task that we associate it with,
 * - run a query (= for matches) and a translation (= for InstantTranslation),
 * - deleted the LangaugeResource and the task.
 *
 * Testing what users can do with LanguageResources in addition is NOT part of this test.
 */
class DeepLLanguageResourceApiTest extends \ZfExtended_Test_ApiTestcase {
    
    /**
     * ServiceType according the Service's namespace.
     * @var string
     */
    const SERVICE_TYPE = 'editor_Plugins_DeepL';
    
    /**
     * ServiceName according the Service.
     * @var string
     */
    const SERVICE_NAME = 'DeepL';
    
    /**
     * According to addResourceForeachUrl() in editor_Services_ServiceAbstract.
     * @var string
     */
    const RESOURCE_ID = 'editor_Plugins_DeepL_1';
    
    /**
     * Name of the LanguageResource that we create (any name will do).
     * @var string
     */
    const LANGUAGERESOURCE_NAME = 'API Testing (Test DeepL de-en)';
    
    /**
     * Id of the created LanguageResource.
     * @var int
     */
    protected static $languageResourceID;
    
    /**
     * "Settings" for translations.
     */
    const SOURCE_LANG = 'de';
    const SOURCE_LANG_CODE = 4;
    const TARGET_LANG = 'en';
    const TARGET_LANG_CODE = 5;
    
    // For InstantTranslate:
    const TEXT_TO_TRANSLATE = 'PHP Handbuch';
    
    // For matches (see task):
    protected static $expectedTranslations = [
        'PHP Handbuch' => 'PHP manual',
        'Das Haus ist blau.' => 'The house is blue.'
    ];
    
    /**
     * 
     */
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__,
            'sourceLang' => self::SOURCE_LANG,
            'targetLang' => self::TARGET_LANG
        );
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_DeepL_Init', $appState->pluginsLoaded, 'DeepL-Plugin must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertCustomer();
        
        self::$languageResourceID = 476; //$this->createLanguageResource(); TODO reactivate after developing
        
        $api->addImportFile($api->getFile('testcase-de-en.xlf'));
        $api->import($task);
    }
    
    /**
     * Create a DeepL-LanguageResource and store its ID.
     */
    protected function createLanguageResource() {
        $params = [];
        $params['resourceId']  = static::RESOURCE_ID;
        $params['name'] = static::LANGUAGERESOURCE_NAME;
        $params['sourceLang'] = static::SOURCE_LANG_CODE;
        $params['targetLang'] = static::TARGET_LANG_CODE;
        $params['serviceType'] = static::SERVICE_TYPE;
        $params['serviceName'] = static::SERVICE_NAME;
        $response = $this->api()->requestJson('editor/languageresourceinstance', 'POST', $params);
        $responseBody = json_decode($response->getBody());
        self::$languageResourceID = $responseBody->rows->id;
        fwrite(STDOUT, "\n" .'our languageResourceID: ' . self::$languageResourceID . "\n"); // TODO remove output
    }
    
    /**
     * Matches:
     * Run a query-search with our DeepL-LanguageResource
     * and check if the result is as expected.
     */
    public function testQuery() {
        $task = $this->api()->getTask();
        
        // associate languageresource to task
        $params = [];
        $params['languageResourceId'] = self::$languageResourceID;
        $params['taskGuid'] = $task->taskGuid;
        $params['segmentsUpdateable'] = 0;
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);
        
        // open task
        $params = [];
        $params['userState'] = 'edit';
        $params['id'] = $task->id;
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', $params);
        
        // get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segToEdit = $segments[0];
        
        // Do we provide an expected translation at all?
        $this->assertArrayHasKey($segToEdit->source, self::$expectedTranslations, 'Provide an expected translation for: '.$segToEdit->source);
        
        $params = [];
        $params['segmentId'] = $segToEdit->id;
        $params['query'] = $segToEdit->source;
        $this->api()->requestJson('editor/languageresourceinstance/'.self::$languageResourceID.'/query', 'GET', $params);
        $response = json_decode($this->api()->getLastResponse()->getBody());
        $translation = $response->rows[0]->target;
        $this->assertEquals(self::$expectedTranslations[$segToEdit->source], $translation, 'Result of translation is not as expected! Source was:'."\n".$segToEdit->source);
    }
    
    /**
     * InstantTranslate:
     * Run a translation with our DeepL-LanguageResource
     * and check if the result is as expected.
     */
    public function testTranslation() {
        $params = [];
        $params['source']  = static::SOURCE_LANG;
        $params['target'] = static::TARGET_LANG;
        $params['text'] = static::TEXT_TO_TRANSLATE;
        $this->api()->requestJson('editor/instanttranslateapi/translate', 'POST', $params); // TODO: 401 Unauthorized
        $response = $this->api()->getLastResponse();
        fwrite(STDOUT, "\n" .'response requestJson(): ' . print_r($response,1) . "\n"); // TODO remove output
    }

    /**
     * 
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        // self::$api->requestJson('editor/languageresourceinstance'.'/'.self::$languageResourceID, 'DELETE'); TODO reactivate after developing
    }
}