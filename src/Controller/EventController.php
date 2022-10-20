<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_list', methods: ['GET'])]
    #[IsGranted('ROLE_CAN_SHOW_EVENT')]
    public function index(EventRepository $eventRepository): Response
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event, [
            'action' => $this->generateUrl('app_event_create'),
        ]);

        return $this->render('event/list.html.twig', [
            'events' => $eventRepository->findByCriteria(),
            'form' => $form->createView(),
            'currentPage' => 'events',
        ]);
    }

    #[Route('/create', name: 'app_event_create', methods: ['POST'])]
    #[IsGranted('ROLE_CAN_CREATE_EVENT')]
    public function create(Request $request, EventRepository $eventRepository, TranslatorInterface $translator): Response
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event->setAuthor($this->getUser());
                foreach ($event->getParticipants() as $participant) {
                    $participant->addEvent($event);
                }
                $eventRepository->save($event, true);
                $this->addFlash('success', $translator->trans('event.create.form.success'));
            } else {
                $this->addFlash('error', $translator->trans('event.create.form.error.generic'));
            }
        }

        return $this->redirectToRoute('app_event_list');
    }
}
