<?php

namespace App\Controller;

use App\Entity\Idea;
use App\Form\IdeaFormType;
use App\Repository\IdeaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/idea')]
class IdeaController extends AbstractController
{
    #[Route('/create-from-home', name: 'app_idea_create_from_home', methods: ['POST'])]
    public function index(Request $request, IdeaRepository $ideaRepository, TranslatorInterface $translator): Response
    {
        $idea = new Idea();
        $idea->setAuthor($this->getUser());

        $form = $this->createForm(IdeaFormType::class, $idea);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $ideaRepository->save($idea, true);
                $this->addFlash('success', $translator->trans('idea.create_from_home.success'));
            } else {
                $this->addFlash('error', $translator->trans('idea.create_from_home.generic_error'));
            }
        }

        return $this->redirectToRoute('app_home');
    }
}
