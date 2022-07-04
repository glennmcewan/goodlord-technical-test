<?php

namespace App\Controller;

use App\Form\AffordabilityCheckFormType;
use App\Service\AffordabilityChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(Request $request, AffordabilityChecker $affordabilityChecker): Response
    {
        $form = $this->createForm(AffordabilityCheckFormType::class);

        $form->handleRequest($request);

        $affordableProperties = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $bankStatement = $form->get('transactions')->getData();
            $propertiesList = $form->get('properties')->getData();

            // Check the availability against the $affordabilityChecker service
            $affordableProperties = $affordabilityChecker->calculateAffordableProperties($bankStatement->getPathname(), $propertiesList->getPathname());
        }

        return $this->render('index.html.twig', [
            'form' => $form->createView(),
            'affordableProperties' => $affordableProperties,
        ]);
    }
}