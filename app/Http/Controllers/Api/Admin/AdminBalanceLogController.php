<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\UnionBalanceLogRepository;
use Illuminate\Http\Request;

class AdminBalanceLogController extends Controller
{
    public function __construct(protected UnionBalanceLogRepository $repository) {}

    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        $paginator = $this->repository->getPage($page, $perPage);
        return response()->json([
            'items' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
