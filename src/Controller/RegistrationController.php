<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\UserEvent;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private LoggerInterface $logger;

    public function __construct(EmailVerifier $emailVerifier, LoggerInterface $logger)
    {
        $this->emailVerifier = $emailVerifier;
        $this->logger = $logger;
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('user.self_registration.success'));

            $dispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATE);

            return $this->redirectToRoute('app_article_create');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EventDispatcherInterface $dispatcher, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (ExpiredSignatureException $exception) {
            $dispatcher->dispatch(new UserEvent($this->getUser()), UserEvent::SEND_NEW_VERIFICATION_MAIL);
            $this->addFlash('error', $translator->trans('user.registration.verification_email.expired'));
            $this->logger->error($exception->getMessage(), $exception->getTrace());

            return $this->redirectToRoute('app_login');
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $translator->trans($exception->getReason()));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', $translator->trans('mail.verify.email.success'));

        return $this->redirectToRoute('app_home');
    }
}
