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
    public function show($id, Posts $post, PostsRepository $postsRepository, SessionInterface $session): Response
    {
        $post = $postsRepository->findOneBy(['id' => $id]);

        if ($post) {
            return $this->render('posts/show.html.twig', [
                'post' => $post,
            ]);
        } else {
            $session->set('show_post_error', 'Post Not Found');
            return $this->redirectToRoute('app_posts');
        }
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
    #[Route('posts/edit/{id}', name: 'app_posts_edit', methods: ['GET'])]
    public function edit($id, Request $request, EntityManagerInterface $entityManager, SessionInterface $session, PostsRepository $postsRepository): Response
    {
        $userId = $session->get('user_id');

        $post = $postsRepository->findOneBy(['author' => $userId, 'id' => $id]);

        $formData = $session->get('form_data', []);
        $session->remove('form_data');

        if ($post) {
            return $this->render('posts/edit.html.twig', [
                'post' => $post,
                'form_data' => $formData,
            ]);
        } else {
            $session->set('edit_post_error', 'Post Not Found');
            return $this->redirectToRoute('app_authors_panel');
        }

    }

    #[Route('/posts/edit/{id}/submit', name: 'app_posts_edit_submit', methods: ['POST'])]
    public function editSubmit(Request $request, EntityManagerInterface $entityManager, SessionInterface $session, PostsRepository $postsRepository, $id): Response
    {
        $title = $request->request->get('title');
        $banner = $request->request->get('banner');
        $description = $request->request->get('description');
        $resource = $request->request->get('resource');
        $category = $request->request->get('category');
        $tag = $request->request->get('tag');
        $author = $session->get('user_id');
        $date = date("Y-m-d");

        $formData = [
            'title' => $title,
            'banner' => $banner,
            'description' => $description,
            'resource' => $resource,
            'category' => $category,
            'tag' => $tag,
        ];

        foreach ($formData as $key => $value) {
            if (empty($value)) {
                $session->set('create_post_error', 'All fields are required and must be valid.');
                $session->set('form_data', $formData);
                return $this->redirectToRoute('app_posts_edit', ['id' => $id]);
            }
        }

        $post = $postsRepository->find($id);

        if (!$post) {
            $session->set('create_post_error', 'Post not found.');
            return $this->redirectToRoute('app_posts_list');
        }

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

        $session->set('edit_post_message', 'Your post has been edited successfully!');
        $session->remove('form_data');
        return $this->redirectToRoute('app_authors_panel');
    }


    # ---------- SEARCH POST ----------
    #[Route('posts/search', name: 'app_posts_search', methods: ['GET'])]
    public function search(Request $request, PostsRepository $postsRepository): Response
    {
        $keyword = $request->query->get('keyword');

        $posts = $postsRepository->createQueryBuilder('p')
            ->where('p.title LIKE :keyword')
            ->orWhere('p.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->getQuery()
            ->getResult();

        return $this->render('posts/index.html.twig', [
            'posts' => $posts,
        ]);
    }

}