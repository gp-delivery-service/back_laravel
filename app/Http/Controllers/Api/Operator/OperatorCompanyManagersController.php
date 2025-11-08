<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Models\GpCompany;
use App\Models\GpCompanyManager;
use App\Repositories\Admin\CompanyManagerRepository;
use App\Services\NodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OperatorCompanyManagersController extends Controller
{

    protected $itemRepository;

    public function __construct(CompanyManagerRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index($company_id, Request $request)
    {
        $user = Auth::user();

        // Получаем параметр статуса из запроса
        $status = $request->get('status');

        Log::info('Company Managers API called with status: ' . $status);

        $items = $this->itemRepository->getItemsWithPagination($user->id, $company_id, 20, $status);

        Log::info('Company Managers API returned ' . count($items->items()) . ' items');

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function create($company_id, Request $request)
    {
        $user = Auth::user();

        $companyExists = GpCompany::where('id', $company_id)->exists();

        if (!$companyExists) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:gp_company_managers,email',
            'password' => 'required|string',
        ]);

        $validated['company_id'] = $company_id;

        try {
            $created = $this->itemRepository->create($validated);

            if (!$created) {
                return response()->json(['error' => 'Error creating manager'], 500);
            }

            return response()->json(['message' => 'Manager created']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating manager'], 500);
        }
    }

    public function update(Request $request, $company_id, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:gp_company_managers,email,' . $id . ',id',
                'password' => 'nullable|string', // Добавить password как опциональное поле
                'is_active' => 'boolean'
            ]);
            $updated = $this->itemRepository->update($id, $validated);

            if (!$updated) {
                return response()->json(['error' => 'Error updating manager'], 500);
            }

            return response()->json(['message' => 'Manager updated']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating manager: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error updating manager: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = $this->itemRepository->delete($id);

            if (!$deleted) {
                return response()->json(['error' => 'Error deleting manager'], 500);
            }

            return response()->json(['message' => 'Manager deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting manager'], 500);
        }
    }
}
