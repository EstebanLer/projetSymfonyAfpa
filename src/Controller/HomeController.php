<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Stock;
use App\Form\ArticleType;
use App\Form\StockType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

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
    public function formArticle(Article $articles = null, Stock $stock = null,Request $request, EntityManagerInterface $manager) {

        if (!$articles) {
            $articles = new Article();
        }

        if (!$stock) {
            $stock = new Stock();
        }

        $form = $this->createForm(ArticleType::class, $articles);
        $formStock = $this->createForm(StockType::class, $stock);

        $form->handleRequest($request);
        $formStock->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$articles->getStock()) {
                $articles->setStock($stock);
                $manager->persist($stock);
            }

            $articles->getStock()->setQuantity($stock->getQuantity());


            $manager->persist($articles);
            $manager->flush();

            return $this->redirectToRoute("home");

        }

        return $this->render('home/addArticle.html.twig', [
            'formAddArticle' => $form->createView(),
            'formStockArticle' => $formStock->createView(),
            'editMode' => $articles->getId() !== null
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
            $articles = $repo->findOneBy(['reference' => $reference]);
        } else {
            $articles = $repo->findOneBy(['name' => $reference]);
        }

        return $this->render("home/show.html.twig",[
            'articles' => $articles
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

//    /**
//     * @Route("/home/article/search", name="article_search")
//    */
//    public function searchArticle(Request $request) { // Fonction qui permet de chercher un article par nom et par référence
//        $ref = $request->get('find');
//
//        $repo = $this->getDoctrine()->getRepository(Article::class);
//
//        if (preg_match('/^[1-9][0-9]*$/', $ref)) {
//            $articles = $repo->findBy(['reference' => $ref]);
//        } else {
//            $articles = $repo->findBy(['name' => $ref]);
//        }
//
//        if ($articles) {
//            return $this->render("home/show.html.twig",[
//                'articles' => $articles
//            ]);
//        } else {
//            return $this->render('home/index.html.twig');
//        }
//    }

// Version avec ajax post

//    /**
//     * @Route("/article/search", name="article_search")
//     */
//    public function searchArticle(Request $request) { // Fonction qui permet de chercher un article par nom et par référence
//        $ref = $request->getContent();
//
//        $arrArticle = [];
//
//        $repo = $this->getDoctrine()->getRepository(Article::class);
//
//        if (preg_match('/^[1-9][0-9]*$/', $ref)) {
//            $articles = $repo->findBy(['reference' => $ref]);
//        } else {
//            $articles = $repo->findBy(['name' => $ref]);
//        }
//
//        if ($articles) {
//            foreach ($articles as $article) {
//                $arrArticle[] = ['name' => $article->getName(),
//                    'price' => $article->getPrice(),
//                    'quantity' => $article->getStock()->getQuantity(),
//                    'id' => $article->getId()];
//            }
//            return new JsonResponse($arrArticle, 200);
//
//        }
//    }


    //Version avec ajax get
    /**
     * @Route("/article/search/{ref}", name="article_search", methods={"GET"})
     * @param $ref
     */
    public function searchArticle(Request $request, $ref) { // Fonction qui permet de chercher un article par nom et par référence

        $arrArticle = [];

        $repo = $this->getDoctrine()->getRepository(Article::class);

        if (preg_match('/^[1-9][0-9]*$/', $ref)) {
            $articles = $repo->findBy(['reference' => $ref]);
        } else {
            $articles = $repo->findBy(['name' => $ref]);
        }

        if ($articles) {
            foreach ($articles as $article) {
                $arrArticle[] = ['name' => $article->getName(),
                    'price' => $article->getPrice(),
                    'quantity' => $article->getStock()->getQuantity(),
                    'id' => $article->getId()];
            }
            return new JsonResponse($arrArticle, 200);
        } else {
            return new JsonResponse($arrArticle, 204);
        }
    }

    /**
     * @Route("/article/findPrice", name="article_searchByPrice")
     */
    public function findArticleByPriceRange(Request $request) {

        $minPrice = $request->get('minPrice');
        $maxPrice = $request->get('maxPrice');

        $repo = $this->getDoctrine()->getRepository(Article::class);

        $articles = $repo->findByPriceRange($minPrice, $maxPrice);
        if ($articles) {
            return $this->render("home/show.html.twig",[
                'articles' => $articles
            ]);
        } else {
            return $this->render('home/index.html.twig');
        }
    }

}
