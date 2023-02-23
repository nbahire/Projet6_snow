<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentFormType;
use App\Form\TrickFormType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(TrickRepository $trickRepository, Request $request): Response
    {
        //On cherche le numero de page dans l'url
        $page = $request->query->getInt('page', 1);
        $tricks = $trickRepository->findTricksPaginated($page);
        if($request->get('ajax')){
            return new JsonResponse(
                ['content' => $this->renderView('layouts/_content.html.twig', compact('tricks'))]
            );
        }
        return $this->render('main/index.html.twig', compact('tricks'));
    }
    #[Route('/user', name: 'app_user')]
    public function user(): Response
    {
        return $this->render('users/user.html.twig', []);
    }

    #[Route('/tricks/create', name:'app_trick_create', methods:['GET', 'PUT'])]
    #[IsGranted("ROLE_USER")]
    public function create(Request $request, EntityManagerInterface $em, Trick $trick): Response
    {
        $form = $this->createForm(TrickFormType::class, $trick, ['method' => 'PUT']);
        $form->handleRequest($request);

        return $this->render('tricks/create.html.twig', ['trick' => $trick, 'form' => $form->createView()]);
    }

    #[Route('/tricks/{id<[0-9]+>}', name:'app_trick_show', methods:["GET","POST"])]
    public function show(Request $request, Trick $trick, EntityManagerInterface $em, CommentRepository $commentRepository): Response
    {
        $comment = new Comment;
        $comment->setTrick($trick);
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser());
            $em->persist($comment);
            $em->flush();
            return $this->redirectToRoute('app_trick_show', ['id' => $trick->getId()]);

        }
        //On cherche le numero de page dans l'url
        $page = $request->query->getInt('page', 1);
        $comments = $commentRepository->findCommentPaginated($trick->getId(), $page);

        if($request->get('ajax')){
            return new JsonResponse(
                ['content' => $this->renderView('layouts/_comment.html.twig', compact('comments'))]
            );
        }

        return $this->render('tricks/show.html.twig',['trick'=>$trick,'comments'=>$comments,'form' => $form->createView(), '']);
    }

    #[Route('/tricks/{id<[0-9]+>}/edit', name:'app_trick_edit', methods:['GET', 'PUT'])]
    #[IsGranted("ROLE_USER")]
    public function edit(Request $request, EntityManagerInterface $em, Trick $trick): Response
    {
        $form = $this->createForm(TrickFormType::class, $trick, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Trick édité avec succes!');

            return $this->redirectToRoute('app_trick_show', ['id' => $trick->getId()]);
        }
        return $this->render('tricks/edit.html.twig',['trick'=>$trick,'form' => $form->createView()]);

    }

    #[Route('/tricks/{id<[0-9]+>}', name:'app_trick_delete', methods:['DELETE'])]
    #[IsGranted("ROLE_USER")]
    public function delete(Request $request, EntityManagerInterface $em, Trick $trick): Response
    {
        if ($this->isCsrfTokenValid('trick_deletion_' . $trick->getId(), $request->request->get('csrf_token'))) {
            $em->remove($trick);
            $em->flush();
            $this->addFlash('info', 'Trick supprimé avec succes !');
        }
        return $this->redirectToRoute('app_main');
    }

}
