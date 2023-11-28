<?php

namespace App\Service;

use App\Entity\Message;
use App\Helper\ResponseHelper;
use App\Repository\FriendRepository;
use App\Repository\MessageRepository;
use App\Validation\PrivateChatValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class PrivateChatService
{
    private PrivateChatValidator $privateChatValidator;
    private ResponseHelper $responseHelper;
    private MessageRepository $messageRepository;
    private FriendRepository $friendRepository;
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(
        PrivateChatValidator $privateChatValidator,
        ResponseHelper $responseHelper,
        MessageRepository $messageRepository,
        FriendRepository $friendRepository,
        UserService $userService,
        LoggerInterface $logger
    ) {
        $this->privateChatValidator = $privateChatValidator;
        $this->responseHelper = $responseHelper;
        $this->messageRepository = $messageRepository;
        $this->friendRepository = $friendRepository;
        $this->userService = $userService;
        $this->logger = $logger;
    }

    public function handle(array $connections, array $data, string $phoneNumber)
    {
        try {
            $validationErrors = $this->privateChatValidator->validate($data['payload']);
            if (count($validationErrors) > 0) {
                $response = $this->responseHelper->validationError(
                    $data['request_id'] ?? -1,
                    $validationErrors
                );
                $this->logger->error(
                    'ERROR CHAT handle validation: ' .
                    $response .
                    '; Payload: ' .
                    $data['payload']
                );
                $connections[$phoneNumber]->send($response);

                return;
            }

            $friends = $this->friendRepository->findOneByUserPhone(
                $phoneNumber,
                $data['payload']['recipient_number'],
                FriendRepository::APPROVE_STATUS_APPROVED
            );

            if (empty($friends)) {
                $response = $this->responseHelper->notApprovedError($data['request_id'] ?? -1);
                $connections[$phoneNumber]->send($response);

                return;
            }

            $this->messageRepository->addMessageByPhone($phoneNumber, $friends, $data['payload']);

            if (isset($connections[$data['payload']['recipient_number']])) {
                $response = $this->responseHelper->messageResponse($phoneNumber, $data['payload']);
                $connections[$data['payload']['recipient_number']]->send($response);
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR CHAT handle: ' .
                $t->getMessage() .
                ' Trace: ' .
                $t->getTraceAsString()
            );

            return;
        }
    }

    public function getPrivateChatByPhone(Request $request): array
    {
        $chats = [];
        try {
            $user = $this->userService->getUserByToken($request);

            if (empty($user)) {
                $this->logger->error(
                    'ERROR CHAT getPrivateChatByPhone: User not found by Token: ' .
                    $request->headers->get('Authorization')
                );

                return [];
            }

            /** @var Message[] $messages */
            $messages = $this->messageRepository->getMessageHistoryByUserPhone(
                $user->getPhone(),
                $request->get('phone'),
                $request->get('page')
            );

            foreach ($messages as $message) {
                $chats[] = [
                    'content' => $message->getContent(),
                    'type' => $message->getType(),
                    'created_at' => $message->getCreatedAt(),
                ];
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR CHAT getPrivateChatByPhone: ' .
                $t->getMessage() .
                ' Trace: ' .
                $t->getTraceAsString()
            );

            return [];
        }

        return $chats;
    }
}
