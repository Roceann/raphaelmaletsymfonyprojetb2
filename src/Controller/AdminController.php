<?php
// src/Controller/AdminController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(CommentRepository $commentRepo, UserRepository $userRepo): Response
    {
        // Récupérer tous les commentaires (approuvés et non approuvés)
        $comments = $commentRepo->findAll();

        // OU, pour récupérer uniquement les commentaires non approuvés
        // $comments = $commentRepo->findBy(['status' => ['pending', 'disapproved']], ['createdAt' => 'DESC']);

        // Récupérer tous les utilisateurs
        $users = $userRepo->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'comments' => $comments,
            'users' => $users,
        ]);
    }
}