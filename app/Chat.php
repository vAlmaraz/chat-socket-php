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
	 *   "type": "start | message",
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