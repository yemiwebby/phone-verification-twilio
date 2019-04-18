<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use GuzzleHttp\Client;

class RegistrationController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    private $userRepository;

    private $apiService;

    public function __construct(EntityManagerInterface $entityManager, ApiService $apiService)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $entityManager->getRepository('App:User');
        $this->apiService = $apiService;
    }


    /**
     * @Route("/register/page", name="user_registration", methods={"GET"})
     */
    public function registerAction(Request $request)
    {
        return $this->render('registration/index.html.twig');
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/register", name="register", methods={"POST"})
     */
    public function registerUsers(Request $request)
    {
        if($request->request->get('country_code') ) {
            $options = [
                'headers' => [
                    "X-Authy-API-Key" => getenv('TWILIO_AUTHY_API_KEY'),
                    "Content-Type" => "application/x-www-form-urlencoded"
                ],
                'form_params' => [
                    "via" => "sms",
                    "phone_number" => $request->request->get('phone_number'),
                    "country_code" => $request->request->get('country_code'),
                    "code_length" => 6,
                    "locale" => "en"
                ]
            ];
            $response = $this->apiService->sendVerificationCode($options);

            if ($response->success == true) {
                $this->addFlash(
                    'success',
                    $response->message
                );
            }

            $user = [
                'username' => $request->request->get('username'),
                'email' => $request->request->get('email'),
                'country_code' => $request->request->get('country_code'),
                'phone_number' => $request->request->get('phone_number'),
                'password' => $request->request->get('password'),
            ];
            $this->get('session')->set('user', $user);
        }

        return $this->redirectToRoute('verify_page');
    }

    public function checkVerificationCodeStatus()
    {
        $options = [
            'query' => ['uuid' => 'a98b3720-1daf-0137-8014-12c2f5542216'],
            'headers' => ["X-Authy-API-Key" => getenv('TWILIO_AUTHY_API_KEY')]
        ];
        $obj = $this->apiService->checkVerificationCodeStatus($options);
    }


    function updateDatabase($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();
    }
}
