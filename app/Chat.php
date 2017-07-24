<?php
namespace App;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    
	protected $clients;
	private $redis;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
		$this->redis = new \Predis\Client();
		$this->redis->flushDb();
    }
	
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

	/**
	 * $msg 
	 * {
	 *   "id": "1",
	 *	 "type": "start | message",
	 *   "to": "2",
	 *   "message": "The message"
	 * }
	 */
    public function onMessage(ConnectionInterface $from, $msg) {
        echo sprintf('Connection %d / %d with message "%s"' . "\n", $from->resourceId, count($this->clients), $msg);
		
		$data = json_decode($msg, true);
		if (!isset($data['id']) || !isset($data['type'])) {
			return;
		}
		$me = $data['id'];
		$type = $data['type'];
		
		$response = [
			'status' => 'ok'
		];
		
		switch ($type) {
			case 'start':
				echo sprintf('Saving id: %s' . "\n", $me);
				$this->redis->set($from->resourceId . '_id', $me);
				break;
			case 'message':
				if (!isset($data['to']) || !isset($data['message'])) {
					return;
				}
				$to = $data['to'];
				$message = $data['message'];
				$searchedClients = [];
				foreach ($this->clients as $client) {
					if ($this->redis->get($client->resourceId . '_id') == $to) {
						$searchedClients[] = $client;
					}
				}
				if (empty($searchedClients)) {
					echo sprintf('User with id %s not found' . "\n", $to);
					$response['status'] = 'ko';
					$response['error'] = 'User is not connected';
					$from->send(json_encode($response));
				} else {
					foreach ($searchedClients as $client) {
						echo sprintf('Sending message to user %s' . "\n", $to);
						$response['from'] = $me;
						$response['message'] = $message;
						$client->send(json_encode($response));
					}
				}
				break;
		}
		
		/*
		$parts = explode(' ', $msg);
		$command = strtolower(array_shift($parts));
		$message = implode(' ', $parts);
		switch ($command) {
			default:
			case '/hi':
				$from->send('What do you need?' . PHP_EOL 
					. '/name <<name>>: tell me your name' . PHP_EOL
					. '/all <<message>>: tell everyone something' . PHP_EOL
					. '/eat <<something>>: send you something to eat' . PHP_EOL
					. '/bye: close');
				break;
			case '/name':
				$this->redis->set($from->resourceId . '_name', $message);
				$from->send('Hi ' . $message . '. I am ratchet. Nice to meet you!');
				break;
			case '/all':
				$from->send('Sending message to everyone...');
				foreach ($this->clients as $client) {
					$client->send($message);
				}
				break;
			case '/eat':
				if (empty($message)) {
					$from->send('What do you want to eat?');
				} elseif (in_array($message, ['apple', 'biscuit'])) {
					$from->send('I send you the ' . $message . '.');
				} else {
					$from->send('Sorry, I don\'t have ' . $message . '. I\'m going to the market to buy it.');
				}
				break;
			case '/bye':
				$name = $this->redis->get($from->resourceId . '_name');
				$from->send('Bye ' . $name);
				break;
		}
		*/
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}