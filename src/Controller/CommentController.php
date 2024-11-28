<?php
// src/Controller/CommentController.php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommentController extends AbstractController
{
    #[Route('/comment/new/{postId}', name: 'comment_new', methods: ['POST'])]
    public function new(
        int $postId,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $currentUser = $this->getUser();
        if (!$currentUser->isActive()) {
            return $this->redirectToRoute('home');
        }

        $post = $postRepository->find($postId);

        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setUser($currentUser);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setStatus('pending');

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été soumis et est en attente d\'approbation.');
        }

        return $this->redirectToRoute('post_detail', ['id' => $postId]);
    }

    #[Route('/comment/edit/{id}', name: 'comment_edit', methods: ['GET', 'POST'])]
    public function edit(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $currentUser = $this->getUser();
        if ($comment->getUser() !== $currentUser && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Vous n\'avez pas les droits nécessaires pour modifier ce commentaire.');
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Commentaire modifié avec succès.');

            return $this->redirectToRoute('post_detail', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/comment/delete/{id}', name: 'comment_delete', methods: ['POST'])]
    public function delete(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $currentUser = $this->getUser();
        if ($comment->getUser() !== $currentUser && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Vous n\'avez pas les droits nécessaires pour supprimer ce commentaire.');
        }

        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('post_detail', ['id' => $comment->getPost()->getId()]);
    }

    #[Route('/comment/approve/{id}', name: 'comment_approve', methods: ['POST'])]
    public function approve(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('approve' . $comment->getId(), $request->request->get('_token'))) {
            $comment->setStatus('approved');
            $em->flush();

            $this->addFlash('success', 'Commentaire approuvé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/comment/disapprove/{id}', name: 'comment_disapprove', methods: ['POST'])]
    public function disapprove(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('disapprove' . $comment->getId(), $request->request->get('_token'))) {
            $comment->setStatus('pending');
            $em->flush();

            $this->addFlash('success', 'Commentaire désapprouvé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}