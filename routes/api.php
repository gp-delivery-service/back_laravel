<?php

use App\Http\Controllers\Api\Admin\AdminOperatorsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\App\AppController;
use App\Http\Controllers\Api\Dashboard\CompanyController;
use App\Http\Controllers\Api\Driver\DriverPickupController;
use App\Http\Controllers\Api\Driver\DriverReturnCashController;
use App\Http\Controllers\Api\Driver\DriverUserController;
use App\Http\Controllers\Api\Driver\DriverWorkController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\Manager\ManagerOrderController;
use App\Http\Controllers\Api\Manager\ManagerPickupController;
use App\Http\Controllers\Api\Manager\ManagerUserController;
use App\Http\Controllers\Api\Map\MapGeoController;
use App\Http\Controllers\Api\Multirole\UserController;
use App\Http\Controllers\Api\Operator\OperatorCompaniesController;
use App\Http\Controllers\Api\Operator\OperatorDriversController;
use App\Http\Controllers\Api\Operator\OperatorUserController;
use App\Http\Controllers\Api\Operator\OperatorCompanyManagersController;
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
Route::post('/admin/auth/login', [AdminUserController::class, 'login']);
Route::post('/admin/auth/refresh', [AdminUserController::class, 'refresh']);
Route::middleware(['auth:api_admin', 'role:admin'])->get('/admin/auth/user', [AdminUserController::class, 'user']);
Route::middleware(['auth:api_admin', 'role:admin'])->post('/admin/auth/logout', [AdminUserController::class, 'logout']);
// - OPERATORS
Route::middleware(['auth:api_admin', 'role:admin'])->get('/admin/operators', [AdminOperatorsController::class, 'index']);
Route::middleware(['auth:api_admin', 'role:admin'])->post('/admin/operators', [AdminOperatorsController::class, 'create']);
Route::middleware(['auth:api_admin', 'role:admin'])->put('/admin/operators/{id}', [AdminOperatorsController::class, 'update']);


// OPERATOR
// - AUTH
Route::post('/operator/auth/login', [OperatorUserController::class, 'login']);
Route::post('/operator/auth/refresh', [OperatorUserController::class, 'refresh']);
Route::middleware(['auth:api_operator', 'role:operator'])->get('/operator/auth/user', [OperatorUserController::class, 'user']);
Route::middleware(['auth:api_operator', 'role:operator'])->post('/operator/auth/logout', [OperatorUserController::class, 'logout']);
// - DRIVERS
Route::middleware(['multi_role:operator,admin'])->get('/operator/drivers', [OperatorDriversController::class, 'index']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/drivers', [OperatorDriversController::class, 'create']);
Route::middleware(['multi_role:operator,admin'])->put('/operator/drivers/{id}', [OperatorDriversController::class, 'update']);
// - COMPANIES
Route::middleware(['multi_role:operator,admin'])->get('/operator/companies', [OperatorCompaniesController::class, 'index']);
Route::middleware(['multi_role:operator,admin,manager'])->get('/operator/companies-short', [OperatorCompaniesController::class, 'shortlist']);
Route::middleware(['multi_role:operator,admin'])->post('/operator/companies', [OperatorCompaniesController::class, 'create']);
Route::middleware(['multi_role:operator,admin'])->put('/operator/companies/{id}', [OperatorCompaniesController::class, 'update']);
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
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/work/pickup/{pickupId}', [DriverWorkController::class, 'pickup']);
// - RETURN CASH
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/return-cash/operators', [DriverReturnCashController::class, 'getOperatorsList']);
Route::middleware(['auth:api_driver', 'role:driver'])->post('/driver/return-cash/amount', [DriverReturnCashController::class, 'getReturnCashAmountWithCode']);
Route::middleware(['auth:api_driver', 'role:driver'])->post('/driver/return-cash/confirm', [DriverReturnCashController::class, 'confirmReturnCash']);


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
Route::middleware(['multi_role:operator,admin,manager'])->put('/manager/orders/{id}', [ManagerOrderController::class, 'update']);
// - PICKUPS
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/pickups', [ManagerPickupController::class, 'index']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups', [ManagerPickupController::class, 'store']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups-quick', [ManagerPickupController::class, 'quickStore']);
Route::middleware(['multi_role:operator,admin,manager'])->put('/manager/pickups/{id}', [ManagerPickupController::class, 'update']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups/{id}/add-orders', [ManagerPickupController::class, 'addOrders']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups/{id}/remove-orders', [ManagerPickupController::class, 'removeOrders']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/pickups/{id}/status', [ManagerPickupController::class, 'changeStatus']);


// MULTIROLE
Route::middleware(['multi_role:admin,operator,manager,driver'])->get('/multirole/user', [UserController::class, 'user']);
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

