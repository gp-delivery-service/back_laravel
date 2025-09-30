<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\NewCompanyRequestRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NewCompanyRequestController extends Controller
{
    protected $itemRepository;

    public function __construct(NewCompanyRequestRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    // Публичное API для отправки заявки
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only(['company_name', 'phone']);
            $item = $this->itemRepository->create($data);

            return response()->json([
                'message' => 'Заявка успешно отправлена',
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при создании заявки',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // API для администраторов и операторов - получение списка заявок
    public function index(Request $request)
    {
        $user = Auth::guard('api_admin')->user();
        if (!$user) {
            $user = Auth::guard('api_operator')->user();
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $items = $this->itemRepository->getItemsWithPagination(20);

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    // API для администраторов и операторов - получение информации о заявке
    public function show($id)
    {
        $user = Auth::guard('api_admin')->user();
        if (!$user) {
            $user = Auth::guard('api_operator')->user();
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $item = $this->itemRepository->getItemById($id);

        if (!$item) {
            return response()->json(['error' => 'Заявка не найдена'], 404);
        }

        return response()->json($item);
    }

    // API для администраторов и операторов - удаление заявки
    public function destroy($id)
    {
        $user = Auth::guard('api_admin')->user();
        if (!$user) {
            $user = Auth::guard('api_operator')->user();
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $item = $this->itemRepository->getItemById($id);

        if (!$item) {
            return response()->json(['error' => 'Заявка не найдена'], 404);
        }

        try {
            $deleted = $this->itemRepository->delete($id);

            if ($deleted) {
                return response()->json([
                    'message' => 'Заявка успешно удалена'
                ]);
            } else {
                return response()->json([
                    'error' => 'Ошибка при удалении заявки'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при удалении заявки',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
