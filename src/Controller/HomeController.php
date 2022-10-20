<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'currentPage' => 'home',
            'articles' => $articleRepository->findByCriteria([
                'limit' => 7,
                'query' => $request->query->get('query')
            ]),
        ]);
    }
}
