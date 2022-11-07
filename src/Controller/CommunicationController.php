<?php

namespace App\Controller;

use App\Entity\Message;
use App\Event\MessageEvent;
use App\Form\ContactFormType;
use App\Repository\IdeaRepository;
use App\Repository\MessageRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


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

    #[Route('/contact', name: 'app_communication_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, TranslatorInterface $translator, MessageRepository $messageRepository, EventDispatcherInterface $dispatcher): Response
    {
        $message = new Message();
        $message
            ->setToEmails($this->getParameter('app.from.email'))
            ->setSendAt(new \DateTimeImmutable())
            ->setSubject($translator->trans('communications.contact.subject'))
        ;

        $form = $this->createForm(ContactFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $message->setData([
                    'senderEmail' => $message->getFromEmail(),
                ]);
                $message->setFromEmail($this->getParameter('app.from.email'));

                $messageRepository->save($message, true);
                $dispatcher->dispatch(new MessageEvent($message), MessageEvent::SIMPLE_MESSAGE);
                $this->addFlash('success', $translator->trans('communication.contact.form.success'));
            } else {
                $this->addFlash('error', $translator->trans('communication.contact.form.error.global'));
            }
        }

        return $this->render('communication/contact.html.twig', [
            'currentPage' => 'contact',
            'app_email' => $this->getParameter('app.from.email'),
            'form' => $form->createView(),
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
