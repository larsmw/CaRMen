<?php

namespace CaRMen\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use CaRMen\Repository\CustomerRepository;
use CaRMen\Entity\Customer;
use CaRMen\Form\CustomerForm;
use CaRMen\Security\Voter\PermissionVoter;

#[Route('/customer', name: 'app_customer_')]
final class CustomerController extends AbstractController
{
    #[Route('/', name: 'index')]
    #[IsGranted(PermissionVoter::CUSTOMER_LIST)]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(PermissionVoter::CUSTOMER_VIEW)]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    #[IsGranted(PermissionVoter::CUSTOMER_ADD)]
    public function addCustomer(Request $request, EntityManagerInterface $entityManager) : Response {
        $customer = new Customer();
        $form = $this->createForm(CustomerForm::class,
           $customer,
           ['method' => 'POST','action'=> '/customer/add']
        );
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($form->getData());
                $entityManager->flush();
            }
        }

        return $this->render('web/index.html.twig', [
            'controller_name' => 'ny kunde',
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted(PermissionVoter::CUSTOMER_EDIT)]
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
