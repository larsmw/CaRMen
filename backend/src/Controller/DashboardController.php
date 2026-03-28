<?php

namespace App\Controller;

use App\Repository\ActivityRepository;
use App\Repository\ContactRepository;
use App\Repository\DealRepository;
use App\Repository\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function stats(
        ContactRepository $contacts,
        AccountRepository $accounts,
        DealRepository $deals,
        ActivityRepository $activities,
    ): JsonResponse {
        return $this->json([
            'contacts'       => $contacts->count([]),
            'accounts'       => $accounts->count([]),
            'open_deals'     => $deals->count(['stage' => ['prospecting', 'qualification', 'proposal', 'negotiation']]),
            'pipeline'       => $deals->getPipelineSummary(),
            'pending_tasks'  => $activities->count(['type' => 'task', 'status' => 'planned']),
        ]);
    }
}
