<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class HomeController extends AbstractController
{

    private $apiService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, ApiService $apiService)
    {
        $this->entityManager = $entityManager;
        $this->apiService = $apiService;
    }

    /**
     * @Route("/home", name="home")
     */
    public function index()
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @Route("/verify/page", name="verify_page")
     */
    public function verifyCodePage()
    {
        return $this->render('home/verify.html.twig');
    }


    /**
     * @Route("/verify/code", name="verify_code")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function verifyCode(Request $request, UserPasswordEncoderInterface $encoder)
    {
        try {
            // Get data from session
            $data = $this->get('session')->get('user');
            $verification_code = $request->query->get('verify_code');

            $options = [
                'query' => [
                    'phone_number' => $data['phone_number'],
                    'country_code' => $data['country_code'],
                    'verification_code' => $verification_code
                ],
                'headers' => ["X-Authy-API-Key" => getenv('TWILIO_AUTHY_API_KEY')]
            ];

            $this->apiService->verifyCodeAndSaveUser($options);
            $this->saveUser($encoder,$data);
            return $this->redirectToRoute('home');

        } catch (\Exception $exception) {
            $this->addFlash(
                'error',
                'Verification code is incorrect'
            );
            return $this->redirectToRoute('verify_page');
        }
    }

    public function saveUser(UserPasswordEncoderInterface $encoder, $data)
    {
        $user = new User();
        $user->setUsername($data['username'])
            ->setEmail($data['email'])
            ->setCountryCode($data['country_code'])
            ->setPhoneNumber($data['phone_number'])
            ->setVerified(true)
            ->setPassword($encoder->encodePassword($user, $data['password']))
            ;

        $this->addFlash(
          'success',
          'You phone number has been verified. Log in here'
        );

        // save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
