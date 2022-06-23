<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\User;
use App\Interfaces\StripePayment;
use App\Repository\CardRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function Paiement(CardRepository $cardRepository): RedirectResponse
    {
        $card = $cardRepository->find($this->getUser()->getCard()->getId());

        Stripe::setApiKey(self::STRIPE_SECRET);
        Stripe::setApiVersion('2020-08-27');
        $session = Session::create([
           'line_items' => $card->getStripeLineItems(),
            'mode' => 'payment',
            'success_url' => 'http://localhost:8000/panier',
            'cancel_url' => 'http://localhost:8000/',
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['FR']
            ],
            'metadata' => [
                'cart_id' => $card->getId()
            ]
        ]);
        return new RedirectResponse($session->url);
    }

    #[Route('/webhook', name: 'stripe_hook')]
    public function StripeHook(Request $request)
    {
        $signature = $request->headers->get('stripe-signature');
        $body = $request->getContent();
        $event = Webhook::constructEvent(
            $body,
            $signature,
            'whsec_960676082d4f1a9b7f120e2990a4c76a8758f631cbc716a17a8cc92a24452527'
        );
        if($event->type === 'checkout.session.completed')
        {
            dd($event);
        }

    }
}
