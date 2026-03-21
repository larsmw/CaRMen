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

#[Route('/customer', name: 'app_customer_')]
final class CustomerController extends AbstractController
{
    #[Route('/', name: 'index')]
    #[IsGranted('customer.list')]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('customer.view')]
    public function show(Customer $customer): Response
    {

        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    #[IsGranted('customer.add')]
    public function addCustomer(Request $request, EntityManagerInterface $entityManager) : Response {
        $customer = new Customer();
        $form = $this->createForm(CustomerForm::class,
           $customer,
           ['method' => 'POST','action'=> '/customer/add']
        );

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->beginTransaction();
                try {
                    $entityManager->persist($form->getData());
                    $entityManager->flush();
                    $entityManager->commit();
                  } catch( \Exception $e) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'Could not save customer: ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('web/index.html.twig', [
            'controller_name' => 'ny kunde',
            'addcustomer_form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('customer.delete')]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($customer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('customer.edit')]
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
