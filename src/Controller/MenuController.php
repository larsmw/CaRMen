<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\MenuItem;
use App\Repository\MenuItemRepository;

class MenuController extends AbstractController
{

    public function mainMenu(EntityManagerInterface $em) {
        dump($em);
        
        $entityRepository = $em->getRepository(MenuItem::class);
        dump($entityRepository);

        $menuitems = $entityRepository->findByMenuField('main');
        dump($menuitems);
        $menu = [
            ['url'=>'/','name'=>'Hjem','title'=>'Hjem'],
            ['url'=>'/logout','name'=>'Log ud']
        ];
        return $this->render('menu/main.html.twig',
            ['menu' => $menu]);
    }

    public function get($menuname) {
        error_log($menuname);
    }
}
