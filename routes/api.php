<?php

use App\Http\Controllers\Api\Admin\AdminOperatorsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\App\AppController;
use App\Http\Controllers\Api\Dashboard\CompanyController;
use App\Http\Controllers\Api\Driver\DriverUserController;
use App\Http\Controllers\Api\Manager\ManagerOrderController;
use App\Http\Controllers\Api\Manager\ManagerUserController;
use App\Http\Controllers\Api\Map\MapGeoController;
use App\Http\Controllers\Api\Multirole\UserController;
use App\Http\Controllers\Api\Operator\OperatorCompaniesController;
use App\Http\Controllers\Api\Operator\OperatorDriversController;
use App\Http\Controllers\Api\Operator\OperatorUserController;
use App\Http\Controllers\Api\Operator\OperatorCompanyManagersController;
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

// DRIVER
// - AUTH
Route::post('/driver/auth/sms', [DriverUserController::class, 'sendCode']);
Route::post('/driver/auth/login', [DriverUserController::class, 'login']);
Route::middleware(['auth:api_driver', 'role:driver'])->get('/driver/auth/user', [DriverUserController::class, 'user']);

// MANAGER
// - AUTH
Route::post('/manager/auth/login', [ManagerUserController::class, 'login']);
Route::post('/manager/auth/refresh', [ManagerUserController::class, 'refresh']);
Route::middleware(['auth:api_manager', 'role:manager'])->get('/manager/auth/user', [ManagerUserController::class, 'user']);
Route::middleware(['auth:api_manager', 'role:manager'])->post('/manager/auth/logout', [ManagerUserController::class, 'logout']);
// - ORDERS
Route::middleware(['multi_role:operator,admin,manager'])->get('/manager/orders', [ManagerOrderController::class, 'index']);
Route::middleware(['multi_role:operator,admin,manager'])->post('/manager/orders', [ManagerOrderController::class, 'create']);
Route::middleware(['multi_role:operator,admin,manager'])->put('/manager/orders/{id}', [ManagerOrderController::class, 'update']);


// MULTIROLE
Route::middleware(['multi_role:admin,operator,manager,driver'])->get('/multirole/user', [UserController::class, 'user']);

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