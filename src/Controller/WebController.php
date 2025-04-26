<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebController extends AbstractController
{
    #[Route('/', name: 'app_web')]
    public function index(): Response
    {
        return $this->render('web/index.html.twig', [
            'controller_name' => 'Lars',
        ]);
    }
}
