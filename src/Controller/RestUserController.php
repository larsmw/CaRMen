<?php

namespace App\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CustomerRepository;
use App\Entity\Customer;

/**
 * Rest User Controller
 * @Route("/api", name="api_")
 */
class RestUserController extends AbstractFOSRestController {

    /**
     * List users
     * @Rest\Get("/users")
     *
     * @return Response
     */
    #[Route('/api/users', name: 'api_users')]
    public function getUsersAction(Request $request, CustomerRepository $customerRepository) {
        error_log(__METHOD__);
        $customers = $customerRepository->findAll();
        return $this->handleView($this->view($customers));
    }
}

