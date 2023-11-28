<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/api/users", name="users", methods={"POST"})
     */
    public function getUsers(Request $request): Response
    {
        return $this->json([
            'status' => 'true',
            'payload' => $this->userService->getExistingUsersByPhones($request->getContent(), $request),
        ]);
    }

    /**
     * @Route("/api/users/profile", name="users.profile", methods={"GET"})
     */
    public function getProfile(Request $request): Response
    {
        return $this->json([
            'status' => 'true',
            'payload' => $this->userService->getSelfUser($request),
        ]);
    }
}
