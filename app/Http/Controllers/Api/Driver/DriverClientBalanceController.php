<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\GpClient;
use App\Models\GpDriver;
use App\Repositories\Balance\ClientTransactionsRepository;
use App\Repositories\Balance\DriverTransactionsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverClientBalanceController extends Controller
{
    protected $clientTransactionsRepository;
    protected $driverTransactionsRepository;

    public function __construct(
        ClientTransactionsRepository $clientTransactionsRepository,
        DriverTransactionsRepository $driverTransactionsRepository
    ) {
        $this->clientTransactionsRepository = $clientTransactionsRepository;
        $this->driverTransactionsRepository = $driverTransactionsRepository;
    }

    /**
     * Пополнение кошелька клиента водителем
     */
    public function topUpClientWallet(Request $request)
    {
        $validated = $request->validate([
            'client_phone' => 'required|string|min:8|max:8',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $driver = Auth::guard('api_driver')->user();

        if (!$driver) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Находим клиента по номеру телефона
        $client = GpClient::where('phone', $validated['client_phone'])->first();

        if (!$client) {
            return response()->json([
                'error' => 'Клиент с таким номером телефона не найден',
            ], 404);
        }

        $amount = $validated['amount'];

        try {
            // Пополняем кошелек клиента
            $this->clientTransactionsRepository->wallet_increase($client->id, $amount);

            // Увеличиваем cash_wallet водителя
            $this->driverTransactionsRepository->cash_wallet_increase($driver->id, $amount);

            // Получаем свежие данные после операций
            $updatedClient = GpClient::find($client->id);
            $updatedDriver = GpDriver::find($driver->id);

            return response()->json(
                [
                    'message' => 'Кошелек клиента успешно пополнен',
                    'client' => [
                        'id' => $updatedClient->id,
                        'name' => $updatedClient->name,
                        'phone' => $updatedClient->phone,
                        'new_wallet_balance' => $updatedClient->wallet,
                    ],
                    'driver' => [
                        'new_cash_wallet_balance' => $updatedDriver->cash_wallet,
                    ],
                    'amount' => $amount,
                ],
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при пополнении кошелька: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение информации о клиенте по номеру телефона
     */
    public function getClientInfo(Request $request)
    {
        $validated = $request->validate([
            'client_phone' => 'required|string|min:8|max:8',
        ]);

        $driver = Auth::guard('api_driver')->user();

        if (!$driver) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Находим клиента по номеру телефона
        $client = GpClient::where('phone', $validated['client_phone'])->first();

        if (!$client) {
            return response()->json([
                'error' => 'Клиент с таким номером телефона не найден',
            ], 404);
        }

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'wallet_balance' => $client->wallet,
            ],
        ]);
    }
}
