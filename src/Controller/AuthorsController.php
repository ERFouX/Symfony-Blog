<?php

namespace App\Controller;

use App\Entity\Authors;
use App\Repository\AuthorsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthorsController extends AbstractController
{

    # ---------- DEFAULTS ----------
    #[Route('/authors', name: 'app_authors')]
    public function index(): Response
    {
        return $this->render('authors/index.html.twig', [
            'controller_name' => 'AuthorsController',
        ]);
    }

    #[Route('/authors/panel', name: 'app_authors_panel', methods: ['GET'])]
    public function panel(): Response
    {

        return $this->render('authors/panel.html.twig', []);

    }
    
    # ---------- REDIRECT TO DEFAULT ROUTES ----------
    #[Route('/author', name: 'app_author')]
    public function redirectToRouteAuthors(): Response
    {
        return $this->redirectToRoute('app_authors');
    }

    # ---------- LOGIN ----------
    #[Route('/authors/login', name: 'app_authors_login')]
    public function login(SessionInterface $session): Response
    {
        if ($this->getUser() || $session->has('user_id')) {
            return $this->redirectToRoute('app_authors_panel');
        }

        return $this->render('authors/login.html.twig', []);
    }

    #[Route('/authors/login/validation', name: 'app_authors_login_validation', methods: ['POST'])]
    public function login_validation(Request $request, AuthorsRepository $authorsRepository, SessionInterface $session): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $user = $authorsRepository->findOneBy(['username' => $username]);

        if ($user && password_verify($password, $user->getPassword())) {
            $session->set('user_id', $user->getId());
            return $this->redirectToRoute('app_authors_panel');
        } else {
            $session->set('login_error', 'Wrong username or password');
            return $this->redirectToRoute('app_authors_login');
        }
    }




    # ---------- REGISTER ----------
    #[Route('/authors/register', name:'app_authors_register')]
    public function register(): Response
    {
        return $this->render('authors/register.html.twig');
    }
    #[Route('/authors/register/validation', name: 'app_authors_register_validation', methods: ['POST'])]
    public function register_validation(Request $request, EntityManagerInterface $entityManager): Response
    {
        # Create New Author
        $author = new Authors();
        $author->setUsername($request->request->get('username'));
        $author->setPassword(password_hash($request->request->get('password'), PASSWORD_DEFAULT));
        $author->setProfileImage($request->request->get('profile_image'));
        $author->setEmail($request->request->get('email'));

        try {
            $entityManager->persist($author);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->redirectToRoute('app_authors_panel');
    }
}
