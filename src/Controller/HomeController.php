<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\User;
use App\Interfaces\StripePayment;
use App\Repository\CardRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private const STRIPE_PUBLIC="pk_test_51LDS6oA12F9voeSitX9EW5nUYmUACngYXOgA5sVM4OwPm7tr5kY9IJBqoPYrDPa93svOZS5pCd8RjDGBiPWdHy4s00K0WOXm4x";
    private const STRIPE_SECRET="sk_test_51LDS6oA12F9voeSiIzsk5PnBRFe0N620aphfygYQfW7E01x8nQ77Jogkym1niMPiNsFB1GQINw7j2pWhnedmZA4I00hBhxZHUk";

    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('home/index.html.twig', compact('products'));
    }

    #[Route('/add/{id}', name: 'app_add_product')]
    public function addProduct($id, ProductRepository $productRepository, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        if($user)
        {
            $product = $productRepository->find($id);
            if($user->getCard())
            {
                $card = $user->getCard();
                $card->addProduct($product);
                $em->persist($card);
            }
            else
            {
                $card = new Card();
                $card->addProduct($product);
                $user->setCard($card);
                $em->persist($card);
                $em->persist($user);
            }


            $em->flush();
            return $this->redirectToRoute('app_home');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/panier', name: 'app_panier')]
    public function Panier(ProductRepository $productRepository, UserRepository $userRepository, CardRepository $cardRepository): Response
    {
        $total = 0.0;
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $card = $cardRepository->find($user->getCard()->getId());
        $products = $card->getProduct();

        foreach($products as $product)
        {
            $total += $product->getPrice();
        }

        return $this->render('home/panier.html.twig', compact('products', 'total'));
    }

    #[Route('/paiement', name: 'app_pay')]
    public function Paiement(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy(['panier' => true]);
        $paiement = new StripePayment(self::STRIPE_SECRET);

        $paiement->startPayment($products);
        return $this->render('paiement.html.twig');
    }
}
