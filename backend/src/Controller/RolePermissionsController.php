<?php

namespace App\Controller;

use App\Entity\RolePermissions;
use App\Repository\RolePermissionsRepository;
use App\Security\PermissionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/role-permissions')]
class RolePermissionsController extends AbstractController
{
    #[Route('', name: 'api_role_permissions_list', methods: ['GET'])]
    public function list(RolePermissionsRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $map = $repo->getMap();

        // Return all known roles, defaulting to empty permissions if not yet configured
        $roles = ['ROLE_USER', 'ROLE_SALES', 'ROLE_MANAGER', 'ROLE_ADMIN'];
        $result = [];
        foreach ($roles as $role) {
            $result[] = [
                'role'        => $role,
                'permissions' => $map[$role] ?? [],
            ];
        }

        return $this->json($result);
    }

    #[Route('/{role}', name: 'api_role_permissions_update', methods: ['PUT'])]
    public function update(
        string $role,
        Request $request,
        RolePermissionsRepository $repo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $validRoles = ['ROLE_USER', 'ROLE_SALES', 'ROLE_MANAGER', 'ROLE_ADMIN'];
        if (!in_array($role, $validRoles, true)) {
            return $this->json(['error' => 'Unknown role.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $permissions = array_values(array_intersect(
            $data['permissions'] ?? [],
            PermissionVoter::PERMISSIONS,
        ));

        $rp = $repo->find($role);
        if (!$rp) {
            $rp = new RolePermissions($role);
            $em->persist($rp);
        }
        $rp->setPermissions($permissions);
        $em->flush();

        return $this->json(['role' => $role, 'permissions' => $permissions]);
    }

    #[Route('/available', name: 'api_permissions_available', methods: ['GET'])]
    public function available(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->json(PermissionVoter::PERMISSIONS);
    }
}
