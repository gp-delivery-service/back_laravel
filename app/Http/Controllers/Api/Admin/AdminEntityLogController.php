<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\EntityLogRepository;
use Illuminate\Http\Request;

class AdminEntityLogController extends Controller
{
    public function __construct(protected EntityLogRepository $repository) {}

    public function index(Request $request)
    {
        $entityType = $request->input('entity_type');
        $entityId = $request->input('entity_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        if (!$entityType || !$entityId) {
            return response()->json(['error' => 'entity_type and entity_id are required'], 400);
        }

        $paginator = $this->repository->getEntityLogs($entityType, $entityId, $page, $perPage);
        
        return response()->json([
            'items' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
} 