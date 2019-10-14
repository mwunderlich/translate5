<?php
use Translate5\FrontEndMessageBus\MessageBus;

require __DIR__ . '/bus-server/vendor/autoload.php';
require __DIR__.'/bus-server/Configuration.php';//message bus config
require __DIR__.'/Configuration.php';//server bus client config

/**
 * TODO use eclipse external tools for running / restart while development
 * 
 * TODO SSL
 * the recommended way to use SSL is via proxying either with nginx or apache.
 * add the following in the SSL vserver config: ProxyPass /tobedefined/ ws://server.domain:9056/
 * in javascript new WebSocket("wss://server.domain/tobedefined/");
 */
$loop   = React\EventLoop\Factory::create();
$bus = new MessageBus();

// PHP Server: Open internal server for connection from traditional PHP application
// FIXME make server and port configurable, must be a dedicated config for the MessageBus server 
//   and must be independant from the same config for the client in editor_Plugins_FrontEndMessageBus_Bus for each instance (coming from translate5 config)
// FIXME use unix sockets here if possible, where possible means if IP is a localdevice, see comment in notify method too
//   React\Socket\Server is able to deal with unix:// schema so far
//   the counterpart in editor_Plugins_FrontEndMessageBus_Bus::notify (Zend_Http_Client using Zend_Http_Client_Adapter_Socket) too.
$webSockPhp = new React\Socket\Server(MESSAGE_BUS_SERVER_IP.':'.MESSAGE_BUS_SERVER_PORT, $loop);
$server = new React\Http\Server([$bus, 'processServerRequest']);
$server->listen($webSockPhp);
$server->on('error', function(\Exception $error) {
    //FIXME error handling
    error_log($error);
    //TODO: use the Translate5\FrontEndMessageBus for the error handling
});

$clientConfig=new editor_Plugins_FrontEndMessageBus_Configuration();
// WebSocket Server: open public server for connections from Browsers 
// FIXME make server and port configurable, see above
$app = new \Ratchet\App($clientConfig->getDomain(),$clientConfig->getPort(), '0.0.0.0', $loop);
$app->route('/translate5', $bus);
$app->run();