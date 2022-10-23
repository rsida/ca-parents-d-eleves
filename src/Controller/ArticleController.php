<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleMedia;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/create', name: 'app_article_create')]
    #[IsGranted('ROLE_CAN_CREATE_ARTICLE')]
    public function create(Request $request, ArticleRepository $articleRepository, TranslatorInterface $translator): Response
    {
        $article = new Article();
        $article->setAuthor($this->getUser());

        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var UploadedFile[] $medias */
                if ($medias = $form->get('medias')->getData()) {
                    foreach ($medias as $media) {
                        $newMediaName = sha1(uniqid(date('YmdHis')));
                        $articleMedia = new ArticleMedia();
                        $article->addMedia($articleMedia);
                        $articleMedia
                            ->setName($newMediaName)
                            ->setOriginalName(pathinfo($media->getClientOriginalName(), PATHINFO_FILENAME))
                            ->setExtension(pathinfo($media->getClientOriginalName(), PATHINFO_EXTENSION))
                            ->setMimeType($media->getMimeType())
                            ->setPath(ArticleMedia::DEFAULT_PUBLIC_PATH)
                        ;

                        $media->move(
                            $this->getParameter('kernel.project_dir').'/public/'.$articleMedia->getPath(),
                            $articleMedia->getFullName()
                        );
                    }
                }

                $articleRepository->save($article, true);

                return $this->redirectToRoute('app_article_list');
            } else {
                $this->addFlash('error', $translator->trans('article.create.form.error.generic'));
            }
        }

        return $this->render('article/create.html.twig', [
            'form' => $form->createView(),
            'currentPage' => 'articles',
        ]);
    }

    #[Route('/{id}/update', name: 'app_article_update')]
    #[IsGranted('ROLE_CAN_UPDATE_ARTICLE')]
    public function update(Request $request, ArticleRepository $articleRepository, TranslatorInterface $translator, int $id): Response
    {
        $article = $articleRepository->find($id);
        if (!$article instanceof Article) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $articleRepository->save($article, true);

                return $this->redirectToRoute('app_article_list');
            } else {
                $this->addFlash('error', $translator->trans('article.update.form.error.generic'));
            }
        }

        return $this->render('article/update.html.twig', [
            'form' => $form->createView(),
            'currentPage' => 'articles',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_article_delete')]
    #[IsGranted('ROLE_CAN_DELETE_ARTICLE')]
    public function delete(ArticleRepository $articleRepository, int $id): Response
    {
        $article = $articleRepository->find($id);
        if (!$article instanceof Article) {
            throw $this->createNotFoundException();
        }

        $articleRepository->remove($article, true);

        return $this->redirectToRoute('app_article_list');
    }

    #[Route('/', name: 'app_article_list', methods: ['GET'])]
    #[IsGranted('ROLE_CAN_SHOW_ARTICLE')]
    public function displayList(Request $request, ArticleRepository $articleRepository): Response
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

        return $this->render('article/list.html.twig', [
            'currentPage' => 'articles',
            'currentPageIndex' => $page,
            'currentLimit' => $limit,
            'articles' => $articleRepository->findByCriteria([
                'query' => $request->query->get('query')
            ], $page, $limit),
            'numberOfPage' => $articleRepository->getNumberOfPageFromFindByCriteria([
                'query' => $request->query->get('query')
            ], $limit),
        ]);
    }

    #[Route('/{slug}', name: 'app_article_show', methods: ['GET'])]
    public function show(ArticleRepository $articleRepository, string $slug): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $articleRepository->findOneBy(['slug' => $slug]),
            'currentPage' => 'articles',
        ]);
    }
}
