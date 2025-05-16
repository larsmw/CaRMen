<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CustomerRepository;
use App\Entity\Customer;
use App\Form\CustomerForm;

final class CustomerController extends AbstractController
{
    #[Route('/customer', name: 'app_customer')]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/customer/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }


    #[Route('/customer/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customerItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CustomerForm::class, $customerItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_customer_show',
                               ['id' => $customerItem->getId()],
                               Response::HTTP_SEE_OTHER);
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customerItem,
            'form' => $form,
        ]);
    }


}
