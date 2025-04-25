<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Repositories\Dashboard\CompanyInfoRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    protected $itemRepository;

    public function __construct(CompanyInfoRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index($id, Request $request)
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if($role === 'manager'){
            $id = $user->company_id;
        }

        // Получение списка компаний
        $info = $this->itemRepository->getCompanyInfo($id);
        return response()->json($info);
    }

    private $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager',
        'api_driver' => 'driver',
    ];
}
