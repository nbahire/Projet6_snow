<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use App\Service\JwtService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        UserAuthenticator $authenticator,
        EntityManagerInterface $entityManager,
        SendMailService $mailService,
        JwtService $jwt
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            )
                ->setName($form->get('name')->getData())
                ->setCreatedAt()
            ;
            $entityManager->persist($user);
            $entityManager->flush();
            // On génère le jwt de l'utilisateur
            // On crée le header
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256',
            ];

            // On crée le payload
            $payload = [
                'user_id' => $user->getId(),
            ];
            // On génère le token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

            $mailService->send(
                'no_reply@snowtricks.com',
                $user->getEmail(),
                'Activation de votre compte sur snowTricks',
                'confirmation_email',
                compact('user', 'token'),
            );

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/{token}', name: 'app_verify_email', methods: ['GET', 'POST'])]
    public function verifyUser(string $token, JwtService $jwtService, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // On vérifie si le token est valide, n'a pas expiré et n'a pas été modifié
        if ($jwtService->isValid($token) &&
           !$jwtService->isExpired($token) &&
           $jwtService->checkSignature($token, $this->getParameter('app.jwtsecret'))
        ) {
            // On récupère le payload
            $payload = $jwtService->getPayload($token);

            // On récupère le user du token
            $user = $userRepository->find($payload['user_id']);

            // On verifie que l'utilisateur existe et que son compte n'est pas active

            if ($user && !$user->isVerified()) {
                $user->setIsVerified(true);
                $entityManager->flush($user);
                $this->addFlash('green', 'Votre compte est activé');

                return $this->redirectToRoute('app_main');
            }
        }
        $this->addFlash('red', 'Le token est invalide ou a expiré');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/verify_resend', name: 'app_verify_email_resend', methods: ['GET', 'POST'])]
    public function verifyUserResend(JwtService $jwtService, SendMailService $mailService): Response
    {
        // On récupère le user
        $user = $this->getUser();

        // On verifie que l'utilisateur existe et que son compte n'est pas active

        if (!$user) {
            $this->addFlash('red', 'Vous devez être connecté pour accéder à cette page');

            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('blue', 'Votre compte est déja verifié');

            return $this->redirectToRoute('app_main');
        }
        // On génère le jwt de l'utilisateur
        // On crée le header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        // On crée le payload
        $payload = [
            'user_id' => $user->getId(),
        ];
        // On génère le token
        $token = $jwtService->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        $mailService->send(
            'no_reply@snowtricks.com',
            $user->getEmail(),
            'Activation de votre compte sur snowTricks',
            'confirmation_email',
            compact('user', 'token'),
        );

        $this->addFlash('green', 'Email de vérification envoyé');

        return $this->redirectToRoute('app_main');
    }
}
