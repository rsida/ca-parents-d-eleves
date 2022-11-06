<?php

namespace App\Controller;

use App\Repository\IdeaRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/communication')]
class CommunicationController extends AbstractController
{
    #[Route('/', name: 'app_communication')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('communication/index.html.twig', [
            'currentPage' => 'communications',
            'currentTab' => null,
        ]);
    }

    #[Route('/idea', name: 'app_communication_idea', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function idea(Request $request, IdeaRepository $ideaRepository): Response
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

        return $this->render('communication/index.html.twig', [
            'currentPage' => 'communications',
            'currentTab' => 'idea',
            'currentPageIndex' => $page,
            'currentLimit' => $limit,
            'ideas' => $ideaRepository->findByCriteria([
                'query' => $request->query->get('query')
            ], $page, $limit),
            'numberOfPage' => $ideaRepository->getNumberOfPageFromFindByCriteria([
                'query' => $request->query->get('query')
            ], $limit),
        ]);
    }
}
