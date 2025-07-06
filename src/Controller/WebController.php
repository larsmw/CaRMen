<?php

namespace CaRMen\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use CaRMen\Entity\Customer;
use CaRMen\Form\CustomerForm;
use CaRMen\Form\SearchForm;

final class WebController extends AbstractController
{

    public function __construct(private Security $security) {}

    #[Route('/', name: 'app_web')]
    public function index(Request $request): Response
    {
        $render_fields = [];
        $render_fields['controller_name'] = 'Lars';
        // Load the user info if logged in.

        $customer = new Customer();
        $form = $this->createForm(CustomerForm::class,
           $customer,
           ['method' => 'POST','action'=> '/customer/add']
        );
        $render_fields['addcustomer_form'] = $form;

        $searchForm = null;
        if ($this->security->isGranted('ROLE_USER')) {
          $searchForm = $this->createForm(SearchForm::class,
                                        ['method' => 'POST', 'action' => '/search']);
          $render_fields['search_form'] = $searchForm;
        }

        return $this->render('web/index.html.twig', $render_fields);
    }

}
