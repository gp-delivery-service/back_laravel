<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\CompanyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorCompaniesController extends Controller
{
    protected $itemRepository;

    public function __construct(CompanyRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index()
    {
        $user = Auth::user();

        $items = $this->itemRepository->getItemsWithPagination($user->id, 20);

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function shortlist()
    {
        $user = Auth::user();

        $items = $this->itemRepository->getAllItems();

        return response()->json($items);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'image' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $created = $this->itemRepository->create($validated);

        if (!$created) {
            return response()->json(['error' => 'Error creating company'], 500);
        }

        return response()->json(['message' => 'Company created']);
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'image' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $updated = $this->itemRepository->update($id, $validated);

        if (!$updated) {
            return response()->json(['error' => 'Error updating company'], 500);
        }

        return response()->json(['message' => 'Company updated']);
    }
}
