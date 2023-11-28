<?php

namespace App\Service;

use App\Entity\Friend;
use App\Repository\FriendRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class UserService
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private FriendRepository $friendRepository;

    public function __construct(
        UserRepository $userRepository,
        LoggerInterface $logger,
        FriendRepository $friendRepository
    ) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->friendRepository = $friendRepository;
    }

    final public function getUserByToken(Request $request)
    {
        try {
            $auth = $request->headers->get('Authorization');

            if (empty($auth) || !str_starts_with($auth, 'Bearer ')) {
                return false;
            }

            $token = explode(' ', $auth)[1];

            $tokenDecoded = base64_decode(explode('.', $auth)[1]);

            $tokenArr = json_decode($tokenDecoded, true);

            if ($tokenArr['exp'] < time()) {
                return false;
            }

            return $this->userRepository->findOneBy([
                'token' => $token,
            ]);
        } catch (Throwable $t) {
            $this->logger->error(
                'ERROR CHAT getPrivateChatByPhone: ' . $t->getMessage() .
                ' Trace: ' . $t->getTraceAsString() .
                ' Payload Header Authorization: ' . $request->headers->get('Authorization')
            );

            return false;
        }
    }

    final public function getExistingUsersByPhones(
        string $phones,
        Request $request
    ): array {
        $usersList = [];
        try {
            /** @var array $content */
            $content = json_decode($phones);

            $filteredPhones = array_map(function ($phone) {
                return preg_replace('/[^0-9.]/i', '', $phone);
            }, $content);

            $users = $this->userRepository->getUsersByPhoneNumbers($filteredPhones);


            /** @var Friend[] $friends */
            $friends = $this->friendRepository->getAllFriends($this->getUserByToken($request)->getPhone());

            foreach ($users as $user) {
                $is_friend = false;
                foreach ($friends as $friend) {
                    if (
                        (
                        $friend->getFriendUser()->getPhone() == $user->getPhone() ||
                        $friend->getOwnerUser()->getPhone() == $user->getPhone()
                        ) && (
                        $friend->getApproveStatus() == FriendRepository::APPROVE_STATUS_APPROVED ||
                        $friend->getApproveStatus() == FriendRepository::APPROVE_STATUS_PENDING
                        )
                    ) {
                        $is_friend = true;
                        break;
                    }
                }

                if ($user->getPhone()) {
                    $usersList[] = [
                    'avatar' => $user->getAvatar(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'phone' => $user->getPhone(),
                    'is_friend' => $is_friend,
                    'updated_at' => $user->getUpdatedAt(),
                    'friends_count' => count($user->getFriendUserFriend()) + count($user->getOwnerUserFriend()),
                    ];
                }
            }
        } catch (Throwable $t) {
            $this->logger->error(
                'ERROR CHAT getPrivateChatByPhone: ' . $t->getMessage() .
                ' --- Trace: ' . $t->getTraceAsString() .
                ' --- Payload: ' . $phones
            );

            return [];
        }

        return $usersList;
    }

    final public function getSelfUser(Request $request): array
    {
        try {
            $user = $this->getUserByToken($request);

            if (empty($user)) {
                $this->logger->error(
                    'ERROR CHAT getSelfUser: User not found by Token: ' .
                    $request->headers->get('Authorization')
                );

                return [];
            }

            return [
                'avatar' => $user->getAvatar(),
                'name' => $user->getName(),
                'username' => $user->getUsername(),
                'phone' => $user->getPhone(),
                'updated_at' => $user->getUpdatedAt(),
            ];
        } catch (Throwable $t) {
            $this->logger->error(
                'ERROR CHAT getPrivateChatByPhone: ' . $t->getMessage() .
                ' --- Trace: ' . $t->getTraceAsString() .
                ' --- Payload: ' . $request->headers->get('Authorization')
            );

            return [];
        }
    }
}
