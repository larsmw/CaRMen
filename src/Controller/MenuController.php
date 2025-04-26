<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\MenuItem;
use App\Repository\MenuItemRepository;

class MenuController extends AbstractController
{

    public function mainMenu(EntityManagerInterface $em) {
        $menuitems = $this->getItems($em, 'main');
        $menu = [];
        foreach ($menuitems as $item) {
            $menu[] = [
                'url' => $item->getRoute(),
                'name' => $item->getName(),
                'title' => $item->getTitle(),
            ];
        }
        return $this->render('menu/main.html.twig',
            ['menu' => $menu]);
    }

    public function adminMenu(EntityManagerInterface $em) {
        $menuitems = $this->getItems($em, 'admin');
        $menu = [];
        foreach ($menuitems as $item) {
            $menu[] = [
                'url' => $item->getRoute(),
                'name' => $item->getName(),
                'title' => $item->getTitle(),
            ];
        }
        return $this->render('menu/main.html.twig',
            ['menu' => $menu]);
    }

    private function getItems($em, $menu) {
        $entityRepository = $em->getRepository(MenuItem::class);
        return $entityRepository->findByMenuName($menu);
    }
    
    public function get($menuname) {
        error_log($menuname);
    }
}
