<?php

namespace App\Service;

use App\Entity\Friend;
use App\Helper\ResponseHelper;
use App\Repository\FriendRepository;
//use Snc\RedisBundle\Client\Phpredis\Client;
use App\Validation\FriendValidator;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class FriendService
{
    private FriendRepository $friendRepository;
    private Client $redisClient;
    private UserService $userService;
    private LoggerInterface $logger;
    private FriendValidator $friendValidator;

    public function __construct(
        FriendRepository $friendRepository,
        Client $redisClient,
        UserService $userService,
        LoggerInterface $logger,
        FriendValidator $friendValidator
    ) {
        $this->friendRepository = $friendRepository;
        $this->redisClient = $redisClient;
        $this->userService = $userService;
        $this->logger = $logger;
        $this->friendValidator = $friendValidator;
    }

    public function addFriendsByPhones(Request $request): bool
    {
        try {
            $user = $this->userService->getUserByToken($request);

            if (empty($user)) {
                $this->logger->error(
                    'ERROR FRIEND addFriendsByPhones: User not found by Token: ' .
                    $request->headers->get('Authorization')
                );

                return false;
            }

            $this->logger->info($request->getContent());

            /** @var array $content */
            $content = json_decode($request->getContent(), true);

            $filteredPhones = array_map(function ($phone) {
                return preg_replace('/[^0-9.]/i', '', $phone);
            }, $content);

            foreach ($filteredPhones as $phoneItem) {
                /** @var Friend $friend */
                $friend = $this->friendRepository->findOneByUserPhone($user->getPhone(), $phoneItem);

                if (empty($friend)) {
                    $this->friendRepository->addFriendWithStatusPendingByPhoneNumbers($user->getPhone(), $phoneItem);
                } elseif (
                    FriendRepository::APPROVE_STATUS_PENDING == $friend->getApproveStatus() &&
                    $friend->getFriendUser()->getPhone() == $user->getPhone()
                ) {
                    $this->friendRepository->setApprovedFriendByPhoneNumbers($friend, $user->getPhone());
                }
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR FRIEND addFriendsByPhones: ' .
                $t->getMessage() .
                ' ---- Trace: ' .
                $t->getTraceAsString()
            );

            return false;
        }

        return true;
    }

    public function getChats(Request $request): array
    {
        $chats = [];

        try {
            $user = $this->userService->getUserByToken($request);

            if (empty($user)) {
                $this->logger->error(
                    'ERROR FRIEND getChats: User not found by Token: ' .
                    $request->headers->get('Authorization')
                );

                return [];
            }

            /** @var Friend[] $friends */
            $friends = $this->friendRepository->findFriendsWithLastMessageByUserPhone($user->getPhone());

            foreach ($friends as $friend) {
                $friendChat = null;

                if ($friend->getOwnerUser()->getId() == $user->getId()) {
                    $friendChat = $friend->getFriendUser();
                } elseif ($friend->getFriendUser()->getId() == $user->getId()) {
                    $friendChat = $friend->getOwnerUser();
                }

                $chats[] = [
                    'avatar' => $friendChat->getAvatar(),
                    'name' => $friendChat->getName(),
                    'phone' => $friendChat->getPhone(),
                    'last_message_at' => $friend->getLastMessage()->getCreatedAt(),
                    'last_message_text' => $friend->getLastMessage()->getContent(),
                ];
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR FRIEND getChats: ' .
                $t->getMessage() . ' ---- Trace: ' .
                $t->getTraceAsString()
            );

            return [];
        }

        return $chats;
    }

    public function getFriends(Request $request)
    {
        $result = [];
        try {
            $user = $this->userService->getUserByToken($request);

            if (empty($user)) {
                $this->logger->error(
                    'ERROR FRIEND getFriends: User not found by Token: ' .
                    $request->headers->get('Authorization')
                );

                return [];
            }

            /** @var Friend[] $friends */
            $friends = $this->friendRepository->getFriends($user->getPhone(), intval($request->get('page')));

            foreach ($friends as $friend) {
                $friendItem = null;

                $isIncome = false;
                if ($friend->getOwnerUser()->getId() == $user->getId()) {
                    $friendItem = $friend->getFriendUser();
                } elseif ($friend->getFriendUser()->getId() == $user->getId()) {
                    $friendItem = $friend->getOwnerUser();
                    $isIncome = true;
                }

                if ($friendItem->getPhone() == $user->getPhone()) {
                    continue;
                }

                $result[] = [
                    'avatar' => $friendItem->getAvatar(),
                    'name' => $friendItem->getName(),
                    'username' => $friendItem->getUsername(),
                    'phone' => $friendItem->getPhone(),
                    'updated_at' => $friendItem->getUpdatedAt(),
                    'status' => $friend->getApproveStatus(),
                    'is_income' => $isIncome,
                ];
            }
        } catch (\Throwable $t) {
            $this->logger->error(
                'ERROR FRIEND getFriends: ' .
                $t->getMessage() . ' ---- Trace: ' .
                $t->getTraceAsString()
            );

            return [];
        }

        return $result;
    }

    public function changeApproveStatusFriendByPhone(Request $request)
    {
        $user = $this->userService->getUserByToken($request);
        if (empty($user)) {
            $this->logger->error(
                'ERROR FRIEND changeApproveStatusFriendByPhone: User not found by Token: ' .
                $request->headers->get('Authorization')
            );

            return false;
        }

        /** @var array $content */
        $content = json_decode($request->getContent(), true);

        $validationErrors = $this->friendValidator->validate($content);
        if (count($validationErrors) > 0) {
            $this->logger->error(
                'ERROR FRIEND changeApproveStatusFriendByPhone validation: ' .
                implode(',', $validationErrors) .
                ' ---- Payload: ' .
                $request->getContent()
            );

            return false;
        }

        if ($content['is_approved']) {
            /** @var Friend $friend */
            $friend = $this->friendRepository->findOneByOwnUserPhone($user->getPhone(), $content['friend_phone']);
            $this->friendRepository->setApprovedFriendByPhoneNumber($friend, $user->getPhone());
        } else {
            /** @var Friend $friend */
            $friend = $this->friendRepository->findOneByUserPhone($user->getPhone(), $content['friend_phone']);
            $this->friendRepository->setBlockedFriendByPhoneNumber($friend, $user->getPhone());
        }

        if (empty($friend)) {
            $this->logger->error(
                'ERROR FRIEND changeApproveStatusFriendByPhone: Friend not found by ownerPhone: ' .
                $user->getPhone() .
                ' and friendPhone: ' .
                $content['friend_phone']
            );

            return false;
        }

        $redisOwnFriends = json_decode($this->redisClient->get($user->getPhone() . '_friends'), true);

        if (null === $redisOwnFriends) {
            $redisOwnFriends = [];
        }

        $redisFriends = json_decode($this->redisClient->get($content['friend_phone'] . '_friends'), true);

        if (null === $redisFriends) {
            $redisFriends = [];
        }

        if (
            $content['is_approved'] &&
            $friend->getIsBlockedByOwner() == false &&
            $friend->getIsBlockedByFriend() == false
        ) {
            if (!in_array($content['friend_phone'], $redisOwnFriends)) {
                $redisOwnFriends[] = $content['friend_phone'];
            }
            if (!in_array($user->getPhone(), $redisFriends)) {
                $redisFriends[] = $user->getPhone();
            }
        } else {
            if (in_array($content['friend_phone'], $redisOwnFriends)) {
                unset($redisOwnFriends[$content['friend_phone']]);
            }
            if (in_array($user->getPhone(), $redisFriends)) {
                unset($redisFriends[$user->getPhone()]);
            }
        }

        $this->redisClient->set($user->getPhone() . '_friends', json_encode($redisOwnFriends));
        $this->redisClient->set($content['friend_phone'] . '_friends', json_encode($redisFriends));

        return true;
    }
}
