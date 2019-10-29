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
 * MessageBus class usable in translate5 (counterpart in translate5 to messageBus in server.php)
 * encapsulates defined commands directly to the MessageBus
 * @method void startSession() startSession($sessionId, stdClass $userData)
 * @method void stopSession() stopSession($sessionId)
 * @method void ping() ping()
 */
class editor_Plugins_FrontEndMessageBus_Bus {
    const CHANNEL = 'instance';
    
    protected $uri;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $logger;
    
    public function __construct() {
        $config = Zend_Registry::get('config');
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.frontendmessagebus');
        if(isset($config->runtimeOptions->plugins->FrontEndMessageBus)) {
            $this->uri = $config->runtimeOptions->plugins->FrontEndMessageBus->messageBusURI;
        }
        else {
            $this->logger->error('E1175', 'FrontEndMessageBus: Missing configuration - runtimeOptions.plugins.FrontEndMessageBus.messageBusURI must be set in configuration.');
        }
    }
    
    //here methods could be implemented if more logic is needed as just passing the arguments directly to the MessageBus via __call 
    // this could be for example necessary to convert entities like editor_Models_Task to native stdClass / array data. 
    // Since only the latter ones can be send to the MessageBus 
    
    /**
     * By default pass all functions directly to the MessageBus
     * @param string $name
     * @param array $args
     */
    public function __call($name, array $args) {
        $this->notify(static::CHANNEL, $name, $args);
    }
    
    public function notify($channel, $command, $data = null) {
        if(empty($this->uri)) {
            return;
        }
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $http->setUri($this->uri);
        
        $http->setParameterPost('instance', ZfExtended_Utils::installationHash('MessageBus'));
        $http->setParameterPost('channel', $channel);
        $http->setParameterPost('command', $command);
        $http->setParameterPost('payload', json_encode($data));
        
        try {
            $this->processResponse($http->request($http::POST));
        }
        catch (Exception $e) {
            $this->logger->exception($e, [
                'level' => $this->logger::LEVEL_WARN
            ]);
        }
        
        //FIXME if host is not reachable, deactivate plugin temporarly (like termtagger DOWN check)
    }
    
    /**
     * Parses and processes the response
     * 
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response) {
        $validStates = [200, 201];
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            //Response status "{status}" in indicates failure in communication with message bus.
            throw new editor_Plugins_FrontEndMessageBus_BusException('E1176', [
                'status' => $response->getStatus(),
                'response' => $response,
            ]);
        }
        
        $responseBody = trim($response->getBody());
        $result = (empty($responseBody)) ? '' : json_decode($responseBody);
        
        //check for JSON errors
        if(json_last_error() > 0){
            //FrontEndMessageBus: parse error in JSON response
            throw new editor_Plugins_FrontEndMessageBus_BusException('E1177', [
                'msg' => json_last_error_msg(),
                'response' => $response,
            ]);
        }
        if(empty($result) && strlen($result) == 0){
            //FrontEndMessageBus: empty JSON response.
            throw new editor_Plugins_FrontEndMessageBus_BusException('E1178', [
                'response' => $response,
            ]);
        }
        
        return true;
    }
}