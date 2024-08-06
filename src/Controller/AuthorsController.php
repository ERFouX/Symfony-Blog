<?php

namespace App\Controller;

use App\Entity\Authors;
use App\Repository\AuthorsRepository;
use App\Repository\PostsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthorsController extends AbstractController
{
    # ---------- DEFAULTS ----------
    #[Route('/authors', name: 'app_authors')]
    public function index(AuthorsRepository $authorsRepository): Response
    {
        $authors = $authorsRepository->findAll();

        return $this->render('authors/index.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/authors/panel', name: 'app_authors_panel', methods: ['GET'])]
    public function panel(SessionInterface $session,PostsRepository $postsRepository): Response
    {
        if ($this->getUser() || $session->has('user_id')) {

            $posts = $postsRepository->findBy(['author' => $session->get('user_id')]);
            return $this->render('authors/panel.html.twig', ['posts' => $posts]);
        }
        else {
            return $this->redirectToRoute('app_authors_login');
        }
    }

    # ---------- REDIRECT TO DEFAULT ROUTES ----------
    #[Route('/author', name: 'app_author')]
    public function redirectToRouteAuthors(): Response
    {
        return $this->redirectToRoute('app_authors');
    }

    # ---------- REGISTER ----------
    #[Route('/authors/register', name: 'app_authors_register')]
    public function register(SessionInterface $session): Response
    {
        if ($this->getUser() || $session->has('user_id')) {
            return $this->redirectToRoute('app_authors_panel');
        } else {
            return $this->render('authors/register.html.twig');
        }
    }
    #[Route('/authors/register/validation', name: 'app_authors_register_validation', methods: ['POST'])]
    public function register_validation(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $profileImage = $request->request->get('profile_image');
        $email = $request->request->get('email');
        $bio = $request->request->get('bio');

        if (empty($username) || empty($password) || empty($email) || empty($bio) || empty($profileImage)) {
            $session->set('register_error', 'All fields are required and must be valid.');
            return $this->redirectToRoute('app_authors_register');
        }

        $author = new Authors();
        $author->setUsername($username);
        $author->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $author->setProfileImage($profileImage);
        $author->setEmail($email);
        $author->setBio($bio);

        try {
            $entityManager->persist($author);
            $entityManager->flush();
        } catch (\Exception) {
            $session->set('register_error', 'Check Your Inputs');
            return $this->redirectToRoute('app_authors_register');
        }

        $session->set('user_id', $author->getId());
        $session->set('username',$author->getUsername());
        $session->set('register_message', 'Your information has been successfully registered');
        return $this->redirectToRoute('app_authors_panel');
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
            $session->set('username',$user->getUsername());
            $session->set('login_message','Welcome '.$user->getUsername());
            return $this->redirectToRoute('app_authors_panel');
        } else {
            $session->set('login_error', 'Wrong username or password');
            return $this->redirectToRoute('app_authors_login');
        }
    }

    # ---------- LOGOUT ----------
    #[Route('/authors/logout', name: 'app_authors_logout', methods: ['GET'])]
    public function logout(SessionInterface $session): Response
    {
        if ($this->getUser() || $session->has('user_id')) {
            $session->clear();
        }
        $session->set('logout_message', 'You have been logged out');
        return $this->redirectToRoute('app_authors_login');
    }
    
}