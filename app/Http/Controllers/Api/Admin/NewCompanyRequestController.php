<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewCompanyRequest;
use App\Repositories\Admin\NewCompanyRequestRepository;
use App\Services\NodeService;  // Добавить импорт
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $data['status'] = NewCompanyRequest::STATUS_PENDING;
            $item = $this->itemRepository->create($data);

             // Отправляем уведомление всем админам через NodeService
            NodeService::notifyNewCompanyRequest([
                'id' => $item->id,
                'company_name' => $item->company_name,
                'phone' => $item->phone,
            ]);

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

        $status = $request->query('status');
        $search = $request->query('search');

        $items = $this->itemRepository->getItemsWithPagination(20, $status, $search);


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

    // API для администраторов и операторов - обновление статуса заявки
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::guard('api_admin')->user();
        if (!$user) {
            $user = Auth::guard('api_operator')->user();
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,accepted,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $item = $this->itemRepository->getItemById($id);

        if (!$item) {
            return response()->json(['error' => 'Заявка не найдена'], 404);
        }

        try {
            $updated = $this->itemRepository->updateStatus($id, $request->input('status'));

            if ($updated) {
                NodeService::notifyCompanyRequestsRefresh();
                return response()->json([
                    'message' => 'Статус заявки успешно обновлен',
                    'data' => $this->itemRepository->getItemById($id)
                ]);
            } else {
                return response()->json([
                    'error' => 'Ошибка при обновлении статуса заявки'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при обновлении статуса заявки',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // API для получения количества pending заявок
    public function getPendingCount()
    {
        $user = Auth::guard('api_admin')->user();
        if (!$user) {
            $user = Auth::guard('api_operator')->user();
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $count = $this->itemRepository->getPendingCount();
            return response()->json([
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при получении количества заявок',
                'message' => $e->getMessage()
            ], 500);
        }
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
                NodeService::notifyCompanyRequestsRefresh();
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
