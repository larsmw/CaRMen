<?php

namespace CaRMen\Controller;

use CaRMen\Entity\MenuItem;
use CaRMen\Form\MenuItemType;
use CaRMen\Security\Voter\MenuItemVoter;
use CaRMen\Repository\MenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/menu/item')]
final class MenuItemController extends AbstractController
{
    #[Route(name: 'app_menu_item_index', methods: ['GET'])]
    #[IsGranted(MenuItemVoter::EDIT)] // Needs edit permission to 'view' the admin page
    public function index(MenuItemRepository $menuItemRepository): Response
    {
        return $this->render('menu_item/index.html.twig', [
            'menu_items' => $menuItemRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_menu_item_new', methods: ['GET', 'POST'])]
    #[IsGranted(MenuItemVoter::CREATE)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $menuItem = new MenuItem();
        $form = $this->createForm(MenuItemType::class, $menuItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
              $entityManager->persist($menuItem);
              $entityManager->flush();
              $entityManager->commit();
            } catch( \Exception $e) {
              $entityManager->rollback();
              throw $e;
            }

            return $this->redirectToRoute('app_menu_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('menu_item/new.html.twig', [
            'menu_item' => $menuItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_menu_item_show', methods: ['GET'])]
    #[IsGranted(MenuItemVoter::EDIT)]
    public function show(MenuItem $menuItem): Response
    {
        return $this->render('menu_item/show.html.twig', [
            'menu_item' => $menuItem,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_menu_item_edit', methods: ['GET', 'POST'])]
    #[IsGranted(MenuItemVoter::EDIT)]
    public function edit(Request $request, MenuItem $menuItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MenuItemType::class, $menuItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
              $entityManager->persist($menuItem);
              $entityManager->flush();
              $entityManager->commit();
            } catch( \Exception $e) {
              $entityManager->rollback();
              throw $e;
            }

            return $this->redirectToRoute('app_menu_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('menu_item/edit.html.twig', [
            'menu_item' => $menuItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_menu_item_delete', methods: ['POST'])]
    #[IsGranted(MenuItemVoter::DELETE)]
    public function delete(Request $request, MenuItem $menuItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menuItem->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($menuItem);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_menu_item_index', [], Response::HTTP_SEE_OTHER);
    }
}
