<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\Trick;
use App\Form\CommentFormType;
use App\Form\TrickFormType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

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

    #[Route('/tricks/{slug}', name:'app_trick_show', methods:["GET","POST"])]
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

    #[Route('/tricks-add', name:'app_trick_create')]
    #[IsGranted("ROLE_USER")]
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //On recupere les images
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                //on definie le dossier de destination
                $folder = 'tricks';

                //On appelle le service d'ajout
                $file = $pictureService->add($image, $folder, 300, 300);
                $img = new Image();
                $img->setUrl($file);
                $trick->addImage($img);
            }
            $trick->setAuthor($this->getUser());
            $trick->setSlug(strtolower($slugger->slug($trick->getTitle())));
            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('green', 'Trick ajouté avec succes');

            return $this->redirectToRoute('app_main');

        }
        return $this->render('tricks/create.html.twig', ['trick' => $trick, 'trickCreateForm' => $form->createView()]);
    }

    #[Route('/tricks/{slug}/edit', name:'app_trick_edit')]
    #[IsGranted("ROLE_USER")]
    public function edit(Request $request, Trick $trick, EntityManagerInterface $entityManager, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //On recupere les images
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                //on definie le dossier de destination
                $folder = 'tricks';

                //On appelle le service d'ajout
                $file = $pictureService->add($image, $folder, 300, 300);
                $img = new Image();
                $img->setUrl($file);
                $trick->addImage($img);
            }
            $trick->setAuthor($this->getUser());
            $trick->setSlug(strtolower($slugger->slug($trick->getTitle())));
            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('green', 'Trick modifié avec succes');

            return $this->redirectToRoute('app_main');

        }

        return $this->render('tricks/edit.html.twig', ['trick' => $trick, 'trickCreateForm' => $form->createView()]);

    }

    #[Route('delete/tricks/{id}', name:'app_trick_delete')]
    #[IsGranted("ROLE_USER")]
    public function delete(Request $request, EntityManagerInterface $em, Trick $trick, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('trick_deletion_' . $trick->getId(), $request->request->get('_token'))) {
            $em->remove($trick);
            $em->flush();
        }
        $this->addFlash('green', 'Trick supprimé avec succes !');

        return $this->redirectToRoute('app_main');
    }

    #[Route('delete/image/{id}', name:'app_image_delete', methods:['DELETE'])]
    #[IsGranted("ROLE_USER")]
    public function deleteImage(Request $request, EntityManagerInterface $em, Image $image, PictureService $pictureService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])) {
            $name = $image->getUrl();
            if ($pictureService->delete($name,'tricks', 300, 300)){
                //On supprime l'image
                $em->remove($image);
                $em->flush();
                return new JsonResponse(['success' => true], 200);

            }
            return new JsonResponse(['error' => 'Erreur de suppression'], 400);
        }
        return new JsonResponse(['error' => 'Token invalide'], 400);
    }

}
