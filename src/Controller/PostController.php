<?php
// src/Controller/PostController.php
namespace App\Controller;

// Importation des fonctions globales nécessaires
use function transliterator_transliterate;
use App\Entity\Post;
use App\Form\PostType;
use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/news', name: 'news')]
    public function index(Request $request, PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $categoryId = $request->query->get('category');
        $selectedCategory = null;
        $posts = [];

        if ($categoryId) {
            $selectedCategory = $categoryRepository->find($categoryId);
            if ($selectedCategory) {
                $posts = $postRepository->findBy(['category' => $selectedCategory], ['publishedAt' => 'DESC']);
            } else {
                // Gérer l'erreur si la catégorie n'existe pas
                $this->addFlash('error', 'Catégorie non trouvée.');
            }
        } else {
            $posts = $postRepository->findBy([], ['publishedAt' => 'DESC']);
        }

        $categories = $categoryRepository->findAll();

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'categories' => $categories,
            'selected_category' => $selectedCategory,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/post/new', name: 'post_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $post = new Post();
        $post->setUser($this->getUser());
        $post->setPublishedAt(new \DateTimeImmutable());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pictureFile = $form['picture']->getData();
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception lors du chargement du fichier
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }

                $post->setPicture($newFilename);
            }

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post ajouté avec succès.');

            return $this->redirectToRoute('news');
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/post/edit/{id}', name: 'post_edit')]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image...
            $pictureFile = $form->get('picture')->getData();
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception lors du chargement du fichier
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }

                $post->setPicture($newFilename);
            }
    
            $post->setUpdatedAt(new \DateTimeImmutable());
    
            $entityManager->flush();
    
            $this->addFlash('success', 'Post modifié avec succès.');
    
            return $this->redirectToRoute('news');
        }
    
        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/post/{id}', name: 'post_detail')]
    public function detail(Post $post, Request $request, EntityManagerInterface $em, CommentRepository $commentRepository): Response
    {
        $comment = new Comment();
        $comment->setPost($post);
        if ($this->getUser()) {
            $comment->setUser($this->getUser());
        }
 
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();
 
            return $this->redirectToRoute('post_detail', ['id' => $post->getId()]);
        }
 
        $approvedComments = $commentRepository->findApprovedCommentsByPost($post);
 
        return $this->render('post/detail.html.twig', [
            'post' => $post,
            'comments' => $approvedComments,
            'commentForm' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/post/delete/{id}', name: 'post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
    
        return $this->redirectToRoute('news'); // Correction ici
    }
}