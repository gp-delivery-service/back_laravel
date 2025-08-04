<?php

use App\Http\Controllers\Api\Admin\AdminBalanceLogController;
use App\Http\Controllers\Api\Admin\AdminEntityLogController;
use App\Http\Controllers\Api\Admin\AdminOperatorsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\App\AppController;
use App\Http\Controllers\Api\Dashboard\CompanyController;
use App\Http\Controllers\Api\Driver\DriverPickupController;
use App\Http\Controllers\Api\Driver\DriverClientBalanceController;
use App\Http\Controllers\Api\Driver\DriverReturnCashController;
use App\Http\Controllers\Api\Driver\DriverUserController;
use App\Http\Controllers\Api\Driver\DriverWorkController;
use App\Http\Controllers\Api\Client\ClientUserController;
use App\Http\Controllers\Api\Client\ClientBalanceController;
use App\Http\Controllers\Api\Client\ClientOrdersController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\Manager\ManagerOrderController;
use App\Http\Controllers\Api\Manager\ManagerPickupController;
use App\Http\Controllers\Api\Manager\ManagerUserController;
use App\Http\Controllers\Api\Manager\ManagerCompanyController;
use App\Http\Controllers\Api\Map\MapGeoController;
use App\Http\Controllers\Api\Multirole\UserController;
use App\Http\Controllers\Api\Operator\OperatorCompaniesController;
use App\Http\Controllers\Api\Operator\OperatorCompanyBalanceController;
use App\Http\Controllers\Api\Operator\OperatorDriversController;
use App\Http\Controllers\Api\Operator\OperatorUserController;
use App\Http\Controllers\Api\Operator\OperatorClientsController;
use App\Http\Controllers\Api\Operator\OperatorCompanyManagersController;
use App\Http\Controllers\Api\Operator\OperatorDriverBalanceController;
use App\Http\Controllers\Api\Operator\OperatorReturnCashController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// APP
Route::get('/app', [AppController::class, 'index']);

// ADMIN
// - AUTH
Route::post('/owner/auth/login', [AdminUserController::class, 'login']);
Route::post('/owner/auth/refresh', [AdminUserController::class, 'refresh']);
Route::middleware(['auth:api_admin', 'role:admin'])->get('/owner/auth/user', [AdminUserController::class, 'user']);
Route::middleware(['auth:api_admin', 'role:admin'])->post('/owner/auth/logout', [AdminUserController::class, 'logout']);
// - OPERATORS
Route::middleware(['auth:api_admin', 'role:admin'])->get('/owner/operators', [AdminOperatorsController::class, 'index']);
Route::middleware(['auth:api_admin', 'role:admin'])->post('/owner/operators', [AdminOperatorsController::class, 'create']);
Route::middleware(['auth:api_admin', 'role:admin'])->put('/owner/operators/{id}', [AdminOperatorsController::class, 'update']);
// - OPERATOR BALANCE
Route::middleware(['auth:api_admin', 'role:admin'])->get('/owner/operator-balance/info/{operator_id}', [AdminOperatorsController::class, 'getInfo']);
Route::middleware(['auth:api_admin', 'role:admin'])->post('/owner/operator-balance/cash-clear', [AdminOperatorsController::class, 'clearCash']);
Route::middleware(['auth:api_admin', 'role:admin'])->post('/owner/operator-balance/cash-add', [AdminOperatorsController::class, 'addCash']);
// Route::middleware(['auth:api_admin', 'role:admin'])->post('/owner/operator-balance/balance-increase', [OperatorCompanyBalanceController::class, 'balanceIncrease']);

// - BALANCE LOGS
Route::middleware(['multi_role:operator,admin'])->get('/owner/balance-logs', [AdminBalanceLogController::class, 'index']);
// - ENTITY LOGS
Route::middleware(['multi_role:operator,admin'])->get('/owner/entity-logs', [AdminEntityLogController::class, 'index']);

// OPERATOR
// - AUTH
Route::post('/operator/auth/login', [OperatorUserController::class, 'login']);
Route::post('/operator/auth/refresh', [OperatorUserController::class, 'refresh']);
Route::middleware(['auth:api_operator', 'role:operator'])->get('/operator/auth/user', [OperatorUserController::class, 'user']);
Route::middleware(['auth:api_operator', 'role:operator'])->post('/operator/auth/logout', [OperatorUserController::class, 'logout']);
// - DRIVERS
Route::middleware(['multi_role:operator,admin'])->get('/operator/drivers', [OperatorDriversController::class, 'index']);
Route::middleware(['multi_role:operator,admin'])->get('/operator/drivers/{id}', [OperatorDriversController::class, 'getInfo']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/drivers', [OperatorDriversController::class, 'create']);
Route::middleware(['multi_role:operator,admin'])->put('/operator/drivers/{id}', [OperatorDriversController::class, 'update']);
// - COMPANIES
Route::middleware(['multi_role:operator,admin'])->get('/operator/companies', [OperatorCompaniesController::class, 'index']);
Route::middleware(['multi_role:operator,admin,manager'])->get('/operator/companies-short', [OperatorCompaniesController::class, 'shortlist']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/companies', [OperatorCompaniesController::class, 'create']);
Route::middleware(['multi_role:operator,admin'])->put('/operator/companies/{id}', [OperatorCompaniesController::class, 'update']);
// - COMPANY BALANCE
Route::middleware(['multi_role:operator,admin'])->get('/operator/company-balance/info/{company_id}', [OperatorCompanyBalanceController::class, 'getInfo']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/company-balance/credit-balance-increase', [OperatorCompanyBalanceController::class, 'creditIncrease']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/company-balance/balance-increase', [OperatorCompanyBalanceController::class, 'balanceIncrease']);
// - CLIENTS
Route::middleware(['multi_role:operator,admin'])->get('/operator/clients', [OperatorClientsController::class, 'index']);
Route::middleware(['multi_role:operator,admin'])->get('/operator/clients/{id}', [OperatorClientsController::class, 'getInfo']);
// - DRIVER BALANCE
Route::middleware(['multi_role:operator,admin'])->get('/operator/driver-balance/info/{driver_id}', [OperatorDriverBalanceController::class, 'getInfo']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/driver-balance/balance-increase', [OperatorDriverBalanceController::class, 'balanceIncrease']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/driver-balance/earning-return', [OperatorDriverBalanceController::class, 'returnEarning']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/driver-balance/close-cash', [OperatorDriverBalanceController::class, 'closeCash']);
// Route::middleware(['multi_role:operator,admin'])->post('/operator/driver-balance/credit-balance-increase', [OperatorCompanyBalanceController::class, 'creditIncrease']);

// - MANAGERS
Route::middleware(['multi_role:operator,admin'])->get('/operator/company/{company_id}/managers', [OperatorCompanyManagersController::class, 'index']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/company/{company_id}/managers', [OperatorCompanyManagersController::class, 'create']);
Route::middleware(['multi_role:operator,admin'])->put('/operator/company/{company_id}/managers/{id}', [OperatorCompanyManagersController::class, 'update']);
// - RETURN CASH
Route::middleware(['auth:api_operator', 'role:operator'])->get('/operator/return-cash/{id}', [OperatorReturnCashController::class, 'getReturnCashCode']);


// DRIVER
// - AUTH
Route::post('/driver/auth/sms', [DriverUserController::class, 'sendCode']);
Route::post('/driver/auth/login', [DriverUserController::class, 'login']);
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/auth/user', [DriverUserController::class, 'user']);
// - PICKUPS
Route::post('/driver/pickups/take', [DriverPickupController::class, 'takePickup']);
// - WORK
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/work/open', [DriverWorkController::class, 'availableFlow']);
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/work/workflow', [DriverWorkController::class, 'workflow']);
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/work/closed', [DriverWorkController::class, 'closed']);
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/work/pickup/{pickupId}', [DriverWorkController::class, 'pickup']);
Route::middleware(['auth:api_driver', 'role:driver'])->put('/driver/work/pickup/{pickupId}/mark_as_picked_up', [DriverWorkController::class, 'pickupPickedUp']);
Route::middleware(['auth:api_driver', 'role:driver'])->put('/driver/work/pickup/{pickupId}/mark_as_closed', [DriverWorkController::class, 'pickupClose']);
Route::middleware(['auth:api_driver', 'role:driver'])->put('/driver/work/pickup_order/mark_as_closed', [DriverWorkController::class, 'orderClose']);
// - CLIENT BALANCE
Route::middleware(['auth:api_driver', 'role:driver'])->post('/driver/client-balance/top-up', [DriverClientBalanceController::class, 'topUpClientWallet']);
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/client-balance/info', [DriverClientBalanceController::class, 'getClientInfo']);
// - RETURN CASH
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/return-cash/operators', [DriverReturnCashController::class, 'getOperatorsList']);
Route::middleware(['auth:api_driver', 'role:driver'])->post('/driver/return-cash/amount', [DriverReturnCashController::class, 'getReturnCashAmountWithCode']);
Route::middleware(['auth:api_driver', 'role:driver'])->post('/driver/return-cash/confirm', [DriverReturnCashController::class, 'confirmReturnCash']);
Route::middleware(['auth:api_driver', 'role:driver'])->post('/driver/return-cash/reset-earning', [DriverReturnCashController::class, 'resetEarning']);


// CLIENT
// - AUTH
Route::post('/client/auth/sms', [ClientUserController::class, 'sendCode']);
Route::post('/client/auth/login', [ClientUserController::class, 'login']);
Route::middleware(['auth:api_client', 'role:client'])->get('/client/auth/user', [ClientUserController::class, 'user']);
// - BALANCE
Route::middleware(['auth:api_client', 'role:client'])->get('/client/balance/info', [ClientBalanceController::class, 'getInfo']);
// - ORDERS
Route::middleware(['auth:api_client', 'role:client'])->get('/client/orders', [ClientOrdersController::class, 'index']);
Route::middleware(['auth:api_client', 'role:client'])->get('/client/orders/{id}', [ClientOrdersController::class, 'getInfo']);

// MANAGER
// - AUTH
Route::post('/manager/auth/login', [ManagerUserController::class, 'login']);
Route::post('/manager/auth/refresh', [ManagerUserController::class, 'refresh']);
Route::middleware(['auth:api_manager', 'role:manager'])->get('/manager/auth/user', [ManagerUserController::class, 'user']);
Route::middleware(['auth:api_manager', 'role:manager'])->post('/manager/auth/logout', [ManagerUserController::class, 'logout']);
// - ORDERS
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/orders', [ManagerOrderController::class, 'index']);
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/orders-open', [ManagerOrderController::class, 'allOpen']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/orders', [ManagerOrderController::class, 'create']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/orders-quick', [ManagerOrderController::class, 'createOrderWithPickupAndSearchDriver']);
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/orders/{id}', [ManagerOrderController::class, 'show']);
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/orders/{id}/fields', [ManagerOrderController::class, 'getOrderFields']);
Route::middleware(['multi_role:operator,admin,manager'])->put('/manager/orders/{id}', [ManagerOrderController::class, 'update']);
// - PICKUPS
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/pickups', [ManagerPickupController::class, 'index']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups', [ManagerPickupController::class, 'store']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups-quick', [ManagerPickupController::class, 'quickStore']);
Route::middleware(['multi_role:operator,admin,manager'])->put('/manager/pickups/{id}', [ManagerPickupController::class, 'update']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups/{id}/add-orders', [ManagerPickupController::class, 'addOrders']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups/{id}/remove-orders', [ManagerPickupController::class, 'removeOrders']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups/{id}/status', [ManagerPickupController::class, 'changeStatus']);
// - COMPANIES
Route::middleware(['multi_role:admin,manager'])->get('/manager/companies', [ManagerCompanyController::class, 'index']);
Route::middleware(['multi_role:admin,manager'])->get('/manager/companies/{id}', [ManagerCompanyController::class, 'show']);


// MULTIROLE
Route::middleware(['multi_role:admin,operator,manager,driver,client'])->get('/multirole/user', [UserController::class, 'user']);
Route::middleware(['multi_role:admin,operator,manager,driver'])->post('/multirole/store/upload', [ImageUploadController::class, 'upload']);
Route::middleware(['multi_role:admin,operator,manager,driver'])->post('/multirole/store/delete', [ImageUploadController::class, 'delete']);

// DASHBOARD
Route::middleware(['multi_role:admin,operator,manager'])->get('/dashboard/company/{id}', [CompanyController::class, 'index']);

// MAPS
// - STREETS
Route::get('/map/streets', [MapGeoController::class, 'streets']);
Route::post('/map/streets', [MapGeoController::class, 'createStreet']);
Route::put('/map/streets/{id}', [MapGeoController::class, 'updateStreet']);
// - DISTRICTS
Route::get('/map/districts', [MapGeoController::class, 'districts']);
Route::post('/map/districts', [MapGeoController::class, 'createDistrict']);
Route::put('/map/districts/{id}', [MapGeoController::class, 'updateDistrict']);


// - XNODE
Route::middleware(['xnode.verify'])->get('/xnode/calls', [ManagerPickupController::class, 'calls']);


Route::get('ping', fn() => response()->json(['pong' => true]));
