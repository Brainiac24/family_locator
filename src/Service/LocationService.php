<?php

namespace App\Service;

use App\Helper\ResponseHelper;
use App\Validation\LocationValidator;
use Predis\Client;
//use Snc\RedisBundle\Client\Phpredis\Client;
use Psr\Log\LoggerInterface;

class LocationService
{
    private LocationValidator $locationValidator;
    private ResponseHelper $responseHelper;
    private Client $redisClient;
    private LoggerInterface $logger;

    public function __construct(
        LocationValidator $locationValidator,
        ResponseHelper $responseHelper,
        Client $redisClient,
        LoggerInterface $logger
    ) {
        $this->locationValidator = $locationValidator;
        $this->responseHelper = $responseHelper;
        $this->redisClient = $redisClient;
        $this->logger = $logger;
    }

    public function handle(array $connections, array $data, string $phone_number)
    {
        try {
            $validationErrors = $this->locationValidator->validate($data['payload']);
            if (count($validationErrors) > 0) {
                $response = $this->responseHelper->validationError(
                    $data['request_id'] ?? -1,
                    $validationErrors
                );
                $this->logger->error(
                    'ERROR LOCATION handle validation: ' .
                    $response .
                    ' ---- Payload: ' .
                    $data['payload']
                );
                $connections[$phone_number]->send($response);

                return;
            }
            $this->redisClient->set($phone_number . '_location', json_encode($data['payload']));

            $friends = json_decode($this->redisClient->get($phone_number . '_friends'), true);

            if (!empty($friends)) {
                foreach ($friends as $friend_number) {
                    if (isset($connections[$friend_number])) {
                        $response = $this->responseHelper->locationResponse($friend_number, $data['payload']);
                        $connections[$friend_number]->send($response);
                    }
                }
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR LOCATION handle: ' .
                $t->getMessage() .
                ' ---- Trace: ' .
                $t->getTraceAsString() .
                ' ---- Payload: ' .
                $data['payload']
            );

            return;
        }
    }
}
