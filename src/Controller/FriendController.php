<?php

namespace App\Controller;

use App\Service\FriendService;
use App\Service\PrivateChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class FriendController extends AbstractController
{
    private FriendService $friendService;
    private PrivateChatService $privateChatService;

    public function __construct(FriendService $friendService, PrivateChatService $privateChatService)
    {
        $this->friendService = $friendService;
        $this->privateChatService = $privateChatService;
    }

    /**
     * @Route("/api/friends", name="friends", methods={"POST"})
     */
    public function addFriends(Request $request): Response
    {
        $result = $this->friendService->addFriendsByPhones($request);

        return $this->json([
            'status' => $result,
            'message' => $result ? 'Success' : 'Error',
        ]);
    }

    /**
     * @Route("/api/friends/change_status", name="friends.change_status", methods={"POST"})
     */
    public function changeStatusFriend(Request $request): Response
    {
        $result = $this->friendService->changeApproveStatusFriendByPhone($request);

        return $this->json([
            'status' => $result,
            'message' => $result ? 'Success' : 'Error',
        ]);
    }

    /**
     * @Route("/api/friends/chats", name="friends.chats", methods={"GET"})
     */
    public function getChats(Request $request): Response
    {
        return $this->json([
            'status' => 'true',
            'payload' => $this->friendService->getChats($request),
        ]);
    }

    /**
     * @Route("/api/friends/{phone}/chats/{page?1}", name="friends.phone.chats", methods={"GET"})
     */
    public function getChatHistory(Request $request): Response
    {
        return $this->json([
            'status' => 'true',
            'payload' => $this->privateChatService->getPrivateChatByPhone($request),
        ]);
    }


    /**
     * @Route("/api/friends/{page?1}", name="friends.list", methods={"GET"})
     *
     * @OA\Get(path="/api/friends/{page}", tags={"Friends"},
     *     @OA\Schema(nullable="true",
     *          @OA\Parameter(
     *              name="page",
     *              in="path",
     *              description="The field used to order rewards 1111",
     *     example=1
     *          )
     *      )
     * )
     *
     *
     */
    public function getFriendsList(Request $request): Response
    {
        return $this->json([
            'status' => 'true',
            'payload' => $this->friendService->getFriends($request),
        ]);
    }
}
