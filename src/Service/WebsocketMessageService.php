<?php

namespace App\Service;

use App\Entity\User;
use App\Helper\ResponseHelper;
use App\Repository\UserRepository;
use App\Validation\MessageValidator;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

class WebsocketMessageService
{
    public array $connections;
    private LocationService $locationService;
    private PrivateChatService $privateChatService;
    private MessageValidator $messageValidator;
    private ResponseHelper $responseHelper;
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        LocationService $locationService,
        PrivateChatService $privateChatService,
        MessageValidator $messageValidator,
        ResponseHelper $responseHelper,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        $this->locationService = $locationService;
        $this->privateChatService = $privateChatService;
        $this->messageValidator = $messageValidator;
        $this->responseHelper = $responseHelper;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function handleMessages(ConnectionInterface $conn, $msg)
    {
        try {
            $clearedData = trim(preg_replace('~[\r\n]+~', '', $msg));
            $data = json_decode($clearedData, true);

            $validationErrors = $this->messageValidator->validate($data);

            if (count($validationErrors) > 0) {
                $response = $this->responseHelper->validationError(
                    $data['request_id'] ?? -1,
                    $validationErrors
                );
                $conn->send($response);
                $this->logger->error(
                    'ERROR WEBSOCKET handleMessages validation: ' .
                    $response .
                    ' ---- Payload: ' .
                    $msg
                );

                return;
            }

            $user = $this->checkTokenAndExtractPhoneNumber($data['access_token']);

            if (!$user) {
                $response = $this->responseHelper->authError($data['request_id'] ?? -1);
                $conn->send($response);
                $this->logger->error(
                    'ERROR WEBSOCKET handleMessages User not found by access_token: ' .
                    $response .
                    ' ---- Payload: ' .
                    $msg
                );

                return;
            }

            $this->addToConnections($conn, $user->getPhone());

            switch ($data['type']) {
                case 'location':
                    $this->locationService->handle($this->connections, $data, $user->getPhone());
                    break;
                case 'message':
                    $this->privateChatService->handle($this->connections, $data, $user->getPhone());
                    break;
                default:
                    break;
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR WEBSOCKET handleMessages: ' .
                $t->getMessage() .
                ' --- Trace: ' .
                $t->getTraceAsString() .
                ' --- Payload: ' .
                $msg
            );

            return;
        }
    }

    public function addToConnections(ConnectionInterface $conn, $phoneNumber)
    {
        if (!isset($this->connections[$phoneNumber])) {
            $this->connections[$phoneNumber] = $conn;
        }
    }

    /**
     * @return User|false
     */
    public function checkTokenAndExtractPhoneNumber(string $token)
    {
        $user = $this->userRepository->findOneBy([
            'token' => $token,
        ]);

        if (empty($user)) {
            return false;
        }

        $tokenDecoded = base64_decode(explode('.', $token)[1]);

        $tokenArr = json_decode($tokenDecoded, true);

        if ($tokenArr['exp'] < time()) {
            return false;
        }

        return $user;
    }
}
