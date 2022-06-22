<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('home/index.html.twig', compact('products'));
    }

    #[Route('/add/{id}', name: 'app_add_product')]
    public function addProduct($id, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $product = $productRepository->find($id);
        $product->setPanier(true);
        $em->persist($product);
        $em->flush();
        return $this->redirectToRoute('app_home');
    }

    #[Route('/panier', name: 'app_panier')]
    public function Panier(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy(['panier' => true]);
        dd($products);
        return $this->redirectToRoute('app_home');
    }
}
