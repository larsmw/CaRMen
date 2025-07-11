<?php

namespace CaRMen\Controller;

use CaRMen\Entity\Permission;
use CaRMen\Form\PermissionForm;
use CaRMen\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/permission')]
final class PermissionController extends AbstractController
{
    #[Route(name: 'app_permission_index', methods: ['GET'])]
    public function index(PermissionRepository $permissionRepository): Response
    {
        return $this->render('permission/index.html.twig', [
            'permissions' => $permissionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_permission_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $permission = new Permission();
        $form = $this->createForm(PermissionForm::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
              $entityManager->persist($permission);
              $entityManager->flush();
              $entityManager->commit();
            } catch( \Exception $e) {
              $entityManager->rollback();
              throw $e;
            }

            return $this->redirectToRoute('app_permission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('permission/new.html.twig', [
            'permission' => $permission,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_permission_show', methods: ['GET'])]
    public function show(Permission $permission): Response
    {
        return $this->render('permission/show.html.twig', [
            'permission' => $permission,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_permission_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Permission $permission, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PermissionForm::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_permission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('permission/edit.html.twig', [
            'permission' => $permission,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_permission_delete', methods: ['POST'])]
    public function delete(Request $request, Permission $permission, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$permission->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($permission);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_permission_index', [], Response::HTTP_SEE_OTHER);
    }
}
