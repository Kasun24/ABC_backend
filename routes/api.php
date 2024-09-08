<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\GastroMasterController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\Waiter\Auth\LoginController as WaiterLoginController;
use App\Http\Controllers\Waiter\TableController as WaiterTableController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [LoginController::class, 'login']);

Route::post('/forgot/password', [ResetPasswordController::class, 'forgot_password']);
Route::post('/reset/password/verify', [ResetPasswordController::class, 'reset_password_verify']);
Route::post('/reset/password', [ResetPasswordController::class, 'reset_password']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/role/list', [RoleController::class, 'rolesList']);
    Route::post('/role/create', [RoleController::class, 'createRole']);
    Route::get('/role/{role_id}', [RoleController::class, 'getRole']);
    Route::post('/role/update', [RoleController::class, 'updateRole']);
    Route::post('/role/delete', [RoleController::class, 'deleteRole']);
    Route::get('/permission/list', [PermissionController::class, 'permissionList']);

    Route::get('/user/list', [UserController::class, 'usersList']);
    Route::post('/user/create', [UserController::class, 'createUser']);
    Route::get('/user/{user_id}', [UserController::class, 'getUser']);
    Route::post('/user/update', [UserController::class, 'updateUser']);
    Route::post('/user/delete', [UserController::class, 'deleteUser']);
    Route::get('/user/image/{name}', [UserController::class, 'getImage']);
    Route::post('/user/update/branch', [UserController::class, 'updateUserBranch']);

    Route::get('/branch/list', [BranchController::class, 'branchList']);
    Route::get('/branch/all/list', [BranchController::class, 'branchAllList']);
    Route::post('/branch/create', [BranchController::class, 'createBranches']);
    Route::get('/branch/{branch_id}', [BranchController::class, 'getBranch']);
    Route::post('/branch/update', [BranchController::class, 'updateBranch']);
    Route::post('/branch/delete', [BranchController::class, 'deleteBranch']);
    Route::get('/branches/get-api-branches', [BranchController::class, 'getApiBranches']);
    Route::get('/branch/sync/{branch_id}', [BranchController::class, 'syncApiBranch']);
    Route::post('/branch/retated/list', [BranchController::class, 'branchRetatedList']);

    Route::get('/table/list', [TableController::class, 'tableList']);
    Route::post('/table/create', [TableController::class, 'createTable']);
    Route::get('/table/{table_id}', [TableController::class, 'getTable']);
    Route::post('/table/update', [TableController::class, 'updateTable']);
    Route::post('/table/delete', [TableController::class, 'deleteTable']);

    Route::get('/tax/list', [TaxController::class, 'taxesList']);
    Route::post('/tax/create', [TaxController::class, 'createTax']);
    Route::get('/tax/{tax_id}', [TaxController::class, 'getTax']);
    Route::post('/tax/update', [TaxController::class, 'updateTax']);
    Route::post('/tax/delete', [TaxController::class, 'deleteTax']);

    Route::get('/query/list', [QueryController::class, 'queryList']);
    Route::post('/query/create', [QueryController::class, 'createquery']);
    Route::get('/query/{query_id}', [QueryController::class, 'getQuery']);
    Route::post('/query/update', [QueryController::class, 'updateQuery']);
    Route::post('/query/delete', [QueryController::class, 'deleteQuery']);

    Route::get('/menu-category/list', [MenuCategoryController::class, 'fetchCategory']);

    Route::get('/product/list', [ProductController::class, 'fetchProduct']);

    Route::get('/toping-scenario/list', [ProductController::class, 'scenario_list']);

    Route::get('/toping/list', [ProductController::class, 'toping_list']);

    Route::get('/order/list', [OrderController::class, 'orderList']);
    Route::post('order/update', [OrderController::class, 'updateOrderStatus']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});

Route::get('/api-sync', [GastroMasterController::class, 'syncData']);

// Waiter Authentication Routes
Route::prefix('waiter')->group(function () {
    Route::post('/login', [WaiterLoginController::class, 'login']);
});

// Protected Waiter Routes
Route::group(['prefix' => 'waiter', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [WaiterLoginController::class, 'logout']);

    Route::get('tables/{branch_id}', [WaiterTableController::class, 'tableList']);
    Route::post('tables/{table_id}/status', [WaiterTableController::class, 'updateTableStatus']);
    Route::post('tables/reserve', [WaiterTableController::class, 'reserveTable']);

    Route::post('/complete-order/{order_id}', [OrderController::class, 'completeOrder']);
});

Route::post('/get-table-order-summary/{table_id}', [OrderController::class, 'getOrderDetails']);

Route::get('/winorder/GetNewOrders', [OrderController::class, 'winorderGetNewOrder']);
Route::post('/winorder/SendTrackingStatus', [OrderController::class, 'sendTrackingStatus']);

Route::get('/get-allbranch', [BranchController::class, 'allbranchs']);
Route::get('/getuser/{user_id}', [UserController::class, 'getSpecificUser']);
Route::get('/getpermission/{role_id}', [RoleController::class, 'getRolePermission']);

// Restaurant POS  
Route::get('/tables/list/{branch_id}', [TableController::class, 'tableListByPOS']);
Route::get('/menu-categories/list/{branch_id}', [MenuCategoryController::class, 'category_list_for_qr_app']);
Route::post('/menu-category-dishes/list', [MenuCategoryController::class, 'dish_list_of_the_given_category']);

// Today Order count api
Route::get('/orders/today-count', [DashboardController::class, 'getTodayOrderCount']);
Route::get('/orders/complete-orders', [DashboardController::class, 'getCompletedOrders']);
Route::get('/orders/monthly-count', [DashboardController::class, 'getMonthlyOrderCount']);
Route::post('/get-cart-summary', [OrderController::class, 'getCartSummary']);
Route::post('/send-to-kitchen', [OrderController::class, 'sendToKitchenPOS']);
Route::post('/complete-order/{order_id}', [OrderController::class, 'cashierCompleteOrder']);

Route::post('/cashier-devices/update', [CustomerController::class, 'customerDevicesUpdate']);
Route::post('/cashier-devices/register', [CustomerController::class, 'customerDevicesCreate']);

Route::get('/get-kitchen-details', [OrderController::class, 'getKitchenDetails']);
Route::post('/customer-devices/register', [CustomerController::class, 'customerDevicesCreate']);
Route::post('/customer-devices/update', [CustomerController::class, 'customerDevicesUpdate']);
Route::post('/order-ready', [OrderController::class, 'confirmOrderReady']);
Route::post('/delete-order-item', [OrderController::class, 'deleteOrderItem']);

Route::get('/customer/list', [CustomerController::class, 'customerList']);
Route::get('/customer/all', [CustomerController::class, 'allCustomerList']);
Route::post('/customer/create', [CustomerController::class, 'customerCreate']);
Route::post('/customer/update', [CustomerController::class, 'customerUpdate']);
