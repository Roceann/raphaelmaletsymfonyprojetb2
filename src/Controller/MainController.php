<?php
// src/Controller/MainController.php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', [
            'title' => 'Profile',

        ]);
    }


    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('main/about.html.twig', [
            'title' => 'À propos',
        ]);
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('main/contact.html.twig', [
            'title' => 'Contact',
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render('main/profile.html.twig', [
            'title' => 'Profile',
            'profilePicture' => '<img src="{{ asset(\'uploads/profiles/\' ~ (app.user.profilePicture ?: \'default-avatar.jpg\')) }}" alt="Photo de profil" class="rounded-circle" width="30" height="30">'
        ]);
    }
}