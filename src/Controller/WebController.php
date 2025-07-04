<?php

namespace CaRMen\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use CaRMen\Entity\Customer;
use CaRMen\Form\CustomerForm;
use CaRMen\Form\SearchForm;

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

}
