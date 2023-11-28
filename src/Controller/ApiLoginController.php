<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ImagesService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Sendpulse\RestApi\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiLoginController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;
    }

    /**
     * @Route("/api/auth/phone", name="api_phone_number", methods={"POST"})
     */
    final public function checkPhoneNumber(Request $request): JsonResponse
    {
        $decoded = $request->getContent();
        $phone = json_decode($decoded)->phone;

        $userEntity = $this->userRepository
            ->findOneBy(['phone' => $phone]);

        if (!is_null($userEntity) && (!empty($userEntity))) {
            return $this->json([
                'status' => 'true',
                'message' => 'User was found at db',
            ]);
        } else {
            return $this->json([
                'status' => 'false',
                'message' => 'User missing at db',
            ]);
        }
    }

    /**
     * @Route("/api/registration", name="api_registration", methods={"POST"})
     */
    final public function register(Request $request): JsonResponse
    {
        $decoded = json_decode($request->getContent(), true);
        $username = $decoded['username'];
        $name = $decoded['name'];
        $phone = preg_replace('/[^0-9.]/i', '', $decoded['phone']);
        $role = ['ROLE_ADMIN'];

        $user = new User();
        $user->setUsername($username);
        $user->setName($name);
        $user->setPhone($phone); //todo: should be hashed?
        $user->setRoles($role);
        $user->setAvatar('Not uploaded yet');

        $this->em->persist($user);
        $this->em->flush($user);

        $this->sendCode($user);

        return $this->json([
            'status' => 'true',
            'message' => 'Message with confirmation code was sent',
        ]);
    }

    /**
     * @Route("/api/remove", name="api_remove", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    final public function removeUser(Request $request): JsonResponse
    {
        $decoded = json_decode($request->getContent(), true);
        $phone = $decoded['phone'];
        $userEntity = $this->userRepository
            ->findOneBy(['phone' => $phone]);

        $ownerFriends = $userEntity->getOwnerUserFriend();

        foreach ($ownerFriends as $ownerFriend) {
            $this->em->remove($ownerFriend);
        }

        $friendFriends = $userEntity->getFriendUserFriend();

        foreach ($friendFriends as $friendFriend) {
            $this->em->remove($friendFriend);
        }

        $this->em->flush();

        $this->em->remove($userEntity);
        $this->em->flush($userEntity);

        return $this->json([
            'status' => 'true',
            'message' => 'user successfully remove',
        ]);
    }

    /**
     * @param Request $request
     * @param ImagesService $imagesService
     * @return JsonResponse
     * @Route("/api/upload_image", name="api_upload_image", methods={"POST"})
     */
    final public function uploadImage(Request $request, ImagesService $imagesService): JsonResponse
    {
        $content = $request->request->all();
        $phone = preg_replace('/[^0-9.]/i', '', $content['phone_number']);
        $userEntity = $this->userRepository
            ->findOneBy(['phone' => $phone]);
        if (is_null($userEntity)) {
            $userEntity = $this->userRepository
                ->findOneBy(['phone' => '+' . $phone]);
        }

        $avatar = false;
        if (isset($_FILES['file']) && !empty($_FILES['file'])) {
            $avatar = $imagesService->saveUserAvatar($userEntity->getId());
        }

        if (!$avatar) {
            return $this->json([
                'status' => 'false',
                'message' => 'Sorry, there was an error uploading your file.',
            ]);
        } else {
            $userEntity->setAvatar($avatar);

            $this->em->persist($userEntity);
            $this->em->flush($userEntity);

            return $this->json([
                'status' => 'true',
                'message' => 'Successful upload',
            ]);
        }
    }

    private function sendCode(User $user): JsonResponse
    {
        $api_user_id = $this->getParameter('app.api_user_id');
        $api_secret = $this->getParameter('app.api_secret');
//        $verifyCode = rand(100000, 999998);
        $verifyCode = 999999;
        $phoneNumber = $user->getPhone();
        $apiClient = new ApiClient($api_user_id, $api_secret);

        $data = [
            $phoneNumber => [
                [
                    [
                        'name' => 'test',
                        'type' => 'string',
                        'value' => $verifyCode,
                        'sender_name' => 'Family Locator',
                    ],
                ],
            ],
        ];

        $params = [
            'sender' => 'family-locator',
            'body' => $verifyCode,
            'route' => '{"BY": "international"}',
        ];

        $phones = [$phoneNumber];

//        $apiClient->sendSmsByList($phones, $data, $params);
        $user->setPassword($verifyCode);
        $this->em->persist($user);
        $this->em->flush($user);

        return $this->json([
            'status' => 'true',
            'message' => 'Code was successfully sent',
        ]);
    }

    /**
     * @Route("/api/auth/check_code", name="api_check_code", methods={"POST"})
     */
    final public function checkCode(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent());
        $verifyCode = $content->verify_code;
        if ((empty($content->phone_number)) || (!is_numeric($content->phone_number))) {
            return $this->json([
                'status' => 'false',
                'message' => 'Missed or wrong phone number',
            ]);
        }

        $userEntity = $this->userRepository
            ->findOneBy(['phone' => $content->phone_number]);

        if (empty($userEntity)) {
            return $this->json([
                'status' => 'false',
                'message' => 'User not found',
            ]);
        }

        if ($userEntity->getPassword() !== $verifyCode) {
            return $this->json([
                'status' => 'false',
                'message' => 'Wrong code validation',
            ]);
        } else {
            $jwt = $this->jwtManager->createFromPayload(
                $userEntity,
                ['phone' => $userEntity->getPhone()]
            );
            $userEntity->setToken($jwt);
            $this->em->persist($userEntity);
            $this->em->flush($userEntity);

            return $this->json([
                'status' => 'true',
                'message' => 'Code is valid',
                'access_token' => $jwt,
            ]);
        }
    }
}
