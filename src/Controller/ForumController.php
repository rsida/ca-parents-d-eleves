<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Topic;
use App\Form\NewTopicFormType;
use App\Form\PostFormType;
use App\Repository\PostRepository;
use App\Repository\TopicRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/forum')]
class ForumController extends AbstractController
{
    #[Route('/{slug}/post/{id}/signal', name: 'app_forum_topic_signal_post', methods: ['GET'])]
    public function signal(Request $request, PostRepository $postRepository, TranslatorInterface $translator, int $id): Response
    {
        $post = $postRepository->find($id);
        $post->setSignaled(true);
        $post->setSignaledAt(new \DateTimeImmutable());
        $postRepository->save($post, true);

        $this->addFlash('warning', $translator->trans('topic.signal.success'));

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_forum_topic_list');
    }

    #[Route('/create', name: 'app_forum_create_topic')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, TopicRepository $topicRepository, TranslatorInterface $translator): Response
    {
        $post = new Post();
        $topic = new Topic();
        $post->setAuthor($this->getUser());
        $topic->addPost($post);
        $topic->setAuthor($this->getUser());

        $form = $this->createForm(NewTopicFormType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $topicRepository->save($topic, true);

                return $this->redirectToRoute('app_forum_topic_show', ['slug' => $topic->getSlug()]);
            } else {
                $this->addFlash('error', $translator->trans('topic.create.form.error.generic'));
            }
        }

        return $this->render('topic/create.html.twig', [
            'currentPage' => 'forums',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}', name: 'app_forum_topic_show', methods: ['GET', 'POST'])]
    public function show(Request $request, TopicRepository $topicRepository, PostRepository $postRepository, TranslatorInterface $translator, string $slug): Response
    {
        $topic = $topicRepository->findOneBy(['slug' => $slug]);
        $post = new Post();
        $post->setAuthor($this->getUser());
        $post->setTopic($topic);

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $postRepository->save($post, true);
            } else {
                $this->addFlash('error', $translator->trans('post.create.form.error.generic'));
            }
        }

        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

        return $this->render('topic/show.html.twig', [
            'currentPage' => 'forums',
            'currentPageIndex' => $page,
            'currentLimit' => $limit,
            'numberOfPage' => ceil($topic->getPosts()->count() / $limit),
            'topic' => $topic,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/', name: 'app_forum_topic_list', methods: ['GET'])]
    public function displayList(Request $request, TopicRepository $topicRepository): Response
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

        return $this->render('topic/list.html.twig', [
            'currentPage' => 'forums',
            'currentPageIndex' => $page,
            'currentLimit' => $limit,
            'topics' => $topicRepository->findByCriteria([
                'query' => $request->query->get('query')
            ], $page, $limit),
            'numberOfPage' => $topicRepository->getNumberOfPageFromFindByCriteria([
                'query' => $request->query->get('query')
            ], $limit),
        ]);
    }
}
