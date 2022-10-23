<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, ArticleRepository $articleRepository, EventRepository $eventRepository): Response
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 7);

        return $this->render('home/index.html.twig', [
            'currentPage' => 'home',
            'currentPageIndex' => $page,
            'currentLimit' => $limit,
            'articles' => $articleRepository->findByCriteria([
                'query' => $request->query->get('query')
            ], $page, $limit),
            'numberOfPage' => $articleRepository->getNumberOfPageFromFindByCriteria([
                'query' => $request->query->get('query')
            ], $limit),
            'events' => $eventRepository->findByCriteria(),
        ]);
    }
}
