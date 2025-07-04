<?php

namespace CaRMen\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use CaRMen\Entity\MenuItem;
use CaRMen\Security\Voter\MenuItemVoter;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{

    public function mainMenu(EntityManagerInterface $em) : Response {
        $menu = $this->get($em, 'main');
        return $this->render('menu/main.html.twig',
            ['menu' => $menu]);
    }

    public function adminMenu(EntityManagerInterface $em): Response {
        $menu = $this->get($em, 'admin');
        return $this->render('menu/main.html.twig',
            ['menu' => $menu]);
    }

    public function get(EntityManagerInterface $em, string $menuname) : array {
        $menuitems = $this->getItems($em, $menuname);
        $menu = [];
        foreach ($menuitems as $item) {

            if ($this->isGranted(MenuItemVoter::VIEW, $item)) {
              $menu[] = [
                  'url' => $item->getRoute(),
                  'name' => $item->getName(),
                  'title' => $item->getTitle(),
              ];
            }
        }
        return $menu;
    }

    private function getItems($em, $menu) : array {
        $entityRepository = $em->getRepository(MenuItem::class);
        return $entityRepository->findByMenuName($menu);
    }
}
