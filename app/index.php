<?php
use Ratchet\Http\OriginCheck;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Chat;

require dirname(__DIR__) . '/vendor/autoload.php';
require (__DIR__) . '/Chat.php';

/*
$checkedApp = new OriginCheck(new Chat, array('localhost'));
$checkedApp->allowedOrigins[] = '192.168.1.3';
$checkedApp->allowedOrigins[] = '192.168.1.8';
*/

$server = IoServer::factory(
	new HttpServer(
		new WsServer(
			new Chat()
		)
	),
	8081
);

$server->run();