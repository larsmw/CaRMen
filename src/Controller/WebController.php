<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Customer;
use App\Form\CustomerForm;
use App\Form\SearchForm;

final class WebController extends AbstractController
{
    #[Route('/', name: 'app_web')]
    public function index(Request $request): Response
    {

        $customer = new Customer();
        $form = $this->createForm(CustomerForm::class,
           $customer,
           ['method' => 'POST','action'=> '/customer/add']
        );

        $searchForm = $this->createForm(SearchForm::class,
                                        ['method' => 'POST', 'action' => '/search']);
        return $this->render('web/index.html.twig', [
            'controller_name' => 'Lars',
            'form' => $searchForm,
        ]);
    }

    #[Route('/customer/add', name: 'customer_add')]
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
}
