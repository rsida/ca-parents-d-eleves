<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\UserEvent;
use App\Form\CreateUserFormType;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_list', methods: ['GET'])]
    #[IsGranted('ROLE_CAN_SHOW_USER')]
    public function displayList(UserRepository $userRepository): Response
    {
        return $this->render('user/list.html.twig', [
            'users' => $userRepository->findByCriteria(),
            'currentPage' => 'users',
        ]);
    }

    #[Route('/create', name: 'app_user_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CAN_CREATE_USER')]
    public function create(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ): Response {
        $user = new User();
        $form = $this->createForm(CreateUserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
                $userRepository->save($user, true);
                $this->addFlash('success', $translator->trans('user.create.success'));

                $dispatcher->dispatch(new UserEvent($user), UserEvent::ON_USER_CREATE);
            } else {
                $this->addFlash('error', $translator->trans('user.create.error.generic'));
            }
        }

        return $this->render('user/create.html.twig', [
            'form' => $form->createView(),
            'currentPage' => 'users',
        ]);
    }

    #[Route('/{id}/update', name: 'app_user_update')]
    #[IsGranted('ROLE_CAN_UPDATE_USER')]
    public function update(Request $request, UserRepository $userRepository, TranslatorInterface $translator, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', $translator->trans('user.update.could_not_self_update_error'));
            return $this->redirectToRoute('app_user_list');
        }

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $userRepository->save($user, true);
                $this->addFlash('success', $translator->trans('user.update.success'));

                return $this->redirectToRoute('app_user_list');
            } else {
                $this->addFlash('error', $translator->trans('user.update.error.generic'));
            }
        }

        return $this->render('article/update.html.twig', [
            'form' => $form->createView(),
            'currentPage' => 'users',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete')]
    #[IsGranted('ROLE_CAN_DELETE_USER')]
    public function delete(UserRepository $userRepository, TranslatorInterface $translator, int $id): Response
    {
        $user = $userRepository->find($id);
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', $translator->trans('user.delete.could_not_self_delete_error'));
        } else {
            $this->addFlash('success', $translator->trans('user.delete.success'));
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_user_list');
    }
}
