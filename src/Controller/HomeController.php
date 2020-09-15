<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Stock;
use App\Form\ArticleType;
use App\Form\StockType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     * @param ArticleRepository $repo
     * @return Response
     */
    public function index(ArticleRepository $repo)
    {

        $articles = $repo->findAll();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'articles' => $articles
        ]);
    }

    /**
    * @Route("/", name="homePage")
    */
    public function homePage() {
        return $this->render('home/homePage.html.twig');
    }

    /**
     * @Route ("/home/new", name="add_article")
     * @Route("/home/{reference}/edit", name="article_edit")
     * @param Article|null $article
     * @param Stock|null $stock
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return RedirectResponse|Response
     */
    public function formArticle(Article $article = null, Stock $stock = null,Request $request, EntityManagerInterface $manager) {

        if (!$article) {
            $article = new Article();
        }

        if (!$stock) {
            $stock = new Stock();
        }

        $form = $this->createForm(ArticleType::class, $article);
        $formStock = $this->createForm(StockType::class, $stock);

        $form->handleRequest($request);
        $formStock->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$article->getStock()) {
                $article->setStock($stock);
                $manager->persist($stock);
            }

            $article->getStock()->setQuantity($stock->getQuantity());


            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute("article_show", ['reference' => $article->getReference()]);

        }

        return $this->render('home/addArticle.html.twig', [
            'formAddArticle' => $form->createView(),
            'formStockArticle' => $formStock->createView(),
            'editMode' => $article->getId() !== null
        ]);
    }


    /**
     * @Route("/home/{reference}", name="article_show")
     * @param Article $article
     * @return Response
     */
    public function show($reference) {

        $repo = $this->getDoctrine()->getRepository(Article::class);

        if (preg_match('/^[1-9][0-9]*$/', $reference)) {
            $article = $repo->findOneBy(['reference' => $reference]);
        } else {
            $article = $repo->findOneBy(['name' => $reference]);
        }

        return $this->render("home/show.html.twig",[
            'article' => $article
        ]);
    }


    /**
     * @Route("/home/{reference}/delete", name="article_delete")
     */
    public function deleteArticle(Article $article, EntityManagerInterface $manager) {

        $id_Stock = $article->getStock();

        $repo = $this->getDoctrine()->getRepository(Stock::class);

        $stock = $repo->find($id_Stock);

        $manager->remove($article);
        $manager->remove($stock);

        $manager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/article", name="article_search")
    */
    public function searchArticle(Request $request) {
        $ref = $request->get('find');

        $repo = $this->getDoctrine()->getRepository(Article::class);
        $article = $repo->findOneBy(["reference" => $ref]);

        return $this->render("home/show.html.twig",[
            'article' => $article
        ]);
    }



}
