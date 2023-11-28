<?php

namespace App\Websocket;

use App\Service\WebsocketMessageService;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebsocketMessageHandler implements MessageComponentInterface
{
    protected \SplObjectStorage $connections;
    private WebsocketMessageService $websocketMessageService;

    public function __construct(WebsocketMessageService $websocketMessageService)
    {
        $this->connections = new \SplObjectStorage();
        $this->websocketMessageService = $websocketMessageService;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->connections->attach($conn);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->connections->detach($conn);
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->websocketMessageService->handleMessages($from, $msg);
    }
}
