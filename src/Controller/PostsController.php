<?php

namespace App\Controller;

use App\Entity\Posts;
use App\Repository\PostsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class PostsController extends AbstractController
{
    # ---------- DEFAULTS ----------
    #[Route('/' ,name:'home')]
    public function home( ): Response
    {
        return $this->redirectToRoute('app_posts');
    }
    #[Route('/posts', name: 'app_posts')]
    public function index(PostsRepository $postsRepository): Response
    {
        $posts = $postsRepository->findAll();

        return $this->render('posts/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    # ---------- SHOW POST ----------
    #[Route('/post/show/{id}', name: 'app_posts_show')]
    public function show(Posts $post): Response
    {
        return $this->render('posts/show.html.twig', []);
    }

    # ---------- CREATE POST ----------
    #[Route('/posts/create', name: 'app_posts_create')]
    public function create(SessionInterface $session): Response
    {
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_authors_login');
        }
        return $this->render('posts/create.html.twig', []);
    }
    #[Route('/posts/create/submit', name: 'app_posts_create_submit', methods: ['POST'])]
    public function createSubmit(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $title = $request->request->get('title');
        $banner = $request->request->get('banner');
        $description = $request->request->get('description');
        $resource  = $request->request->get('resource');
        $category = $request->request->get('category');
        $tag = $request->request->get('tag');
        # Fields to be filled automatically
        $author = $session->get('user_id');
        $date = date("Y-m-d");

        if (empty($title) || empty($banner) || empty($description) || empty($author) || empty($date) || empty($category) || empty($tag)) {
            $session->set('create_post_error', 'All fields are required and must be valid.');
            return $this->redirectToRoute('app_posts_create');
        }

        $post = new Posts();
        $post->setTitle($title);
        $post->setBanner($banner);
        $post->setDescription($description);
        $post->setResource($resource);
        $post->setAuthor($author);
        $post->setDate($date);
        $post->setCategory($category);
        $post->setTag($tag);

        $entityManager->persist($post);
        $entityManager->flush();
        $session->set('create_post_message', 'Your post has been created successfully');
        return $this->redirectToRoute('app_authors_panel');
    }

    # ---------- DELETE POST ----------
    #[Route('/posts/delete/{id}', name: 'app_posts_delete')]
    public function delete(Posts $post, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if ($post->getAuthor() == $session->get('user_id')) {
            $entityManager->remove($post);
            $entityManager->flush();
            $session->set('delete_post_message', 'Your post has been deleted successfully');
            return $this->redirectToRoute('app_authors_panel');
        }
        $session->set('delete_post_permission_error','You are not authorized to delete this post');
        return $this->redirectToRoute('app_authors_panel');
    }

    # ---------- EDIT POST ----------
    #[Route('posts/edit/{id}', name: 'app_posts_edit')]
    public function edit($id, Request $request, EntityManagerInterface $entityManager, SessionInterface $session, PostsRepository $postsRepository): Response
    {
        $userId = $session->get('user_id');

        $post = $postsRepository->findOneBy(['author' => $userId, 'id' => $id]);

        if ($post) {
            return $this->render('posts/edit.html.twig', [
                'post' => $post,
            ]);
        } else {
            $session->set('edit_post_error', 'Post Not Found');
            return $this->redirectToRoute('app_authors_panel');
        }
    }

    #[Route('/posts/edit/submit', name: 'app_posts_edit_submit', methods: ['POST'])]
    public function editSubmit(): Response
    {

    }

}
