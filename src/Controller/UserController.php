<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Factory\JsonResponseFactory;
use Doctrine\ORM\Query\Expr\Func;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{

    private $em;
    public function __construct(private JsonResponseFactory $jsonResponseFactory, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse {

        $payload = json_decode($request->getContent(), false);
        $resultData = [];

        if(!isset($payload->email) || !isset($payload->password)){
            $resultData['message'] = 'The all field is required. Email and password';
            $statusCode = 400;
        } else {

            $user = $this->em->getRepository(User::class)->findOneBy(['email'=>$payload->email, 'password'=>$payload->password]);
            if(!$user instanceof User){
                $resultData['message'] = 'Access denid! Invalid crentials.';
                $statusCode = 403;
                return $this->json($resultData, $statusCode); 
            }

            $token = uniqid();
            $user->setToken($token);
            
            $resultData['message']  = 'It\'s OK';
            $resultData['token']    = $token;
            $statusCode             = 200;

            $this->em->flush();

        }

        return $this->json($resultData, $statusCode); 

    }

    #[Route('/test-login', name: 'test_login', methods: ['GET'])]
    public function testLogin(Request $request, ): JsonResponse
    {
        $payload = json_decode($request->getContent(), false);

        $dataTokenError = ['message'=>'Invalid token!'];

        if(!isset($payload->token)){
            return $this->json($dataTokenError, 403);
        }

        if(!$this->checkUserIsAtuthenticated($payload->token)){
            return $this->json($dataTokenError, 403);
        }

        return $this->json(['message'=>'User is authenticated!'], 200);
    }

    private function checkUserIsAtuthenticated($token) {
        $user = $this->em->getRepository(User::class)->findOneBy(['token'=>$token]);
        return ($user instanceof User);
    }

}
