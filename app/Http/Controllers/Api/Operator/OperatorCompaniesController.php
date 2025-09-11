<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\CompanyRepository;
use App\Models\GpCompany;
use App\Models\GpPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function delete($id)
    {
        $user = Auth::user();

        // Находим компанию
        $company = GpCompany::find($id);
        
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Проверяем балансы
        if ($company->credit_balance > 0) {
            return response()->json([
                'error' => 'Невозможно удалить компанию с ненулевым кредитным балансом',
                'details' => 'Кредитный баланс компании: ' . $company->credit_balance
            ], 400);
        }

        if ($company->agregator_side_balance > 0) {
            return response()->json([
                'error' => 'Невозможно удалить компанию с ненулевым балансом агрегатора',
                'details' => 'Баланс агрегатора компании: ' . $company->agregator_side_balance
            ], 400);
        }

        if ($company->balance > 0) {
            return response()->json([
                'error' => 'Невозможно удалить компанию с ненулевым балансом',
                'details' => 'Баланс компании: ' . $company->balance
            ], 400);
        }

        // Проверяем наличие вызовов в таблице gp_pickups
        $pickupsCount = GpPickup::where('company_id', $id)->count();
        
        if ($pickupsCount > 0) {
            return response()->json([
                'error' => 'Невозможно удалить компанию с существующими вызовами',
                'details' => 'У компании есть ' . $pickupsCount . ' вызов(ов)'
            ], 400);
        }

        // Если все проверки пройдены, удаляем компанию
        try {
            DB::beginTransaction();
            
            $deleted = $company->delete();
            
            if (!$deleted) {
                DB::rollBack();
                return response()->json(['error' => 'Error deleting company'], 500);
            }
            
            DB::commit();
            
            return response()->json(['message' => 'Компания успешно удалена']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error deleting company: ' . $e->getMessage()], 500);
        }
    }
}
