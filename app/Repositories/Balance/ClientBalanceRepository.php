<?php

namespace App\Repositories\Balance;

use App\Helpers\LogHelper;
use App\Models\GpClient;
use Illuminate\Support\Facades\DB;

class ClientBalanceRepository
{
    public function addWallet($clientId, $amount, $tag)
    {
        $client = GpClient::find($clientId);
        if (!$client) {
            return null;
        }

        $oldAmount = $client->wallet;
        $tag = $tag ?: 'wallet_update';
        $column = 'wallet';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_clients')
            ->where('id', $clientId)
            ->increment('wallet', $amount);

        $newAmount = GpClient::find($clientId)->wallet;

        DB::table('gp_client_balance_logs')->insert([
            'client_id' => $clientId,
            'amount' => $amount,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'tag' => $tag,
            'column' => $column,
            'user_id' => $userData['user_id'],
            'user_type' => $userData['user_type'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $client->refresh();
    }
}