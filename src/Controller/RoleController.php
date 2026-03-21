<?php

namespace CaRMen\Controller;

use CaRMen\Entity\Role;
use CaRMen\Form\RoleForm;
use CaRMen\Repository\PermissionRepository;
use CaRMen\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/role')]
final class RoleController extends AbstractController
{
    #[Route(name: 'app_role_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): Response
    {
        return $this->render('role/index.html.twig', [
            'roles' => $roleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_role_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleForm::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
              $entityManager->persist($role);
              $entityManager->flush();
              $entityManager->commit();
            } catch( \Exception $e) {
              $entityManager->rollback();
              throw $e;
            }

            return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('role/new.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/permissions', name: 'app_role_permissions', methods: ['GET', 'POST'])]
    public function permissions(
        Request $request,
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $roles       = $roleRepository->findAll();
        $permissions = $permissionRepository->findAll();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('permission_matrix', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $matrix = $request->request->all('matrix'); // [role_id => [permission_id, ...]]

            foreach ($roles as $role) {
                $selectedIds = array_map('intval', $matrix[$role->getId()] ?? []);

                foreach ($role->getPermissions() as $permission) {
                    if (!in_array($permission->getId(), $selectedIds, true)) {
                        $role->removePermission($permission);
                    }
                }

                foreach ($permissions as $permission) {
                    if (in_array($permission->getId(), $selectedIds, true)) {
                        $role->addPermission($permission);
                    }
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_role_permissions');
        }

        return $this->render('role/permissions.html.twig', [
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    #[Route('/{id}', name: 'app_role_show', methods: ['GET'])]
    public function show(Role $role): Response
    {
        return $this->render('role/show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_role_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RoleForm::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('role/edit.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_role_delete', methods: ['POST'])]
    public function delete(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($role);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
    }
}
