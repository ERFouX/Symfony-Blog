<?php

namespace App\Controller;

use App\Entity\Users;
use PharIo\Manifest\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthorsController extends AbstractController
{
    #[Route('/authors', name: 'app_authors')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $author = new Users();
        $author->setName('ERFouX');
        $author->setProfileImage('home/erfoux/');

    }
}
