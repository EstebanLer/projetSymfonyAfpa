<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Stock;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index()
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
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
    */
    public function addArticle(Article $article = null,Request $request, EntityManagerInterface $manager) {

        if (!$article) {
            $article = new Article();
        }

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$article->getId()) {
                $stock = new Stock();
                $stock->setQuantity(100);
                $article->setStock($stock);
            }

            $manager->persist($article);
            $manager->flush();

        }

        return $this->render('home/addArticle.html.twig', [
            'formAddArticle' => $form->createView()
        ]);
    }
}
