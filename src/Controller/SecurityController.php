<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot_password', name: 'app_forgot_password_request')]
    public function request(Request $request, SendMailService $mailService, UserRepository $userRepository, TokenGeneratorInterface $tokenGenerator, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On vachercher l'utilisateur par son e-mail
            $user = $userRepository->findOneByEmail($form->get('email')->getData());
            // On v??rifie si on a un utilisateur
            if($user){
                //On g??n??re un token de r??initialisation
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $entityManager->persist($user);
                $entityManager->flush();

                //On g??n??re un lien de r??initialition du mot de passe
                $url = $this->generateUrl('app_password_reset', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // On cr??e les donn??es du mail
                $context = compact('user', 'url');
                $mailService->send(
                    'no_reply@snowtricks.com',
                    $user->getEmail(),
                    'R??initialisation du mot de passe',
                    'reset_pass_email',
                    $context
                );
                $this->addFlash('green', 'Email envoy?? avec succ??s');
                return $this->redirectToRoute('app_login');

            }
            // $user est null
            $this->addFlash('red', 'Un probl??me est survenu');
            return $this->redirectToRoute('app_login');

        }

        return $this->render('security/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/forgot_password/{token}', name: 'app_password_reset')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // On verifie si on ?? ce token dans la db
        $user = $userRepository->findOneResetToken($token);
        if ($user){
            $form = $this->createForm(ChangePasswordFormType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // On efface le token
                $user->setResetToken('');
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData(),
                    )
                );
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('green', 'Mot de passe chang?? avec succ??s');
                return $this->redirectToRoute('app_login');

            }

            return $this->render('security/reset_password.html.twig', [
                'resetForm' => $form->createView()
            ]);

        }
        $this->addFlash('red', 'Token invalide!');
        return $this->redirectToRoute('app_login');

    }

}
