<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Auth::routes();
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

//Reports url
Route::get('/agreements/download', [App\Http\Controllers\AgreementController::class, 'export'])->name('agreements.export');
Route::get('/servers/download', [App\Http\Controllers\ServerController::class, 'export'])->name('servers.export');
Route::get('/switches/download', [App\Http\Controllers\DeviceController::class, 'export'])->name('switches.export');

Route::get('/files', [App\Http\Controllers\FileController::class, 'index'])->name('files.index');
Route::post('/files', [App\Http\Controllers\FileController::class, 'store'])->name('files.store');
Route::get('/files/create', [App\Http\Controllers\FileController::class, 'create'])->name('files.create');

Route::get('/files/{file}', [App\Http\Controllers\FileController::class, 'show'])->name('files.show');
Route::get('/files/{file}/edit', [App\Http\Controllers\FileController::class, 'edit'])->name('files.edit');
Route::put('/files/{file}', [App\Http\Controllers\FileController::class, 'update'])->name('files.update');

Route::get('/agreements', [App\Http\Controllers\AgreementController::class, 'index'])->name('agreements.index');
Route::post('/agreements', [App\Http\Controllers\AgreementController::class, 'store'])->name('agreements.store');
Route::get('/agreements/create', [App\Http\Controllers\AgreementController::class, 'create'])->name('agreements.create');
Route::get('/agreements/expired', [App\Http\Controllers\AgreementController::class, 'expired'])->name('agreements.expired');
Route::get('/agreements/due', [App\Http\Controllers\AgreementController::class, 'due'])->name('agreements.due');
Route::get('/agreements/{agreement}', [App\Http\Controllers\AgreementController::class, 'show'])->name('agreements.show');
Route::get('/agreements/{agreement}/edit', [App\Http\Controllers\AgreementController::class, 'edit'])->name('agreements.edit');
Route::put('/agreements/{agreement}', [App\Http\Controllers\AgreementController::class, 'update'])->name('agreements.update');

Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
Route::get('/payments/due', [App\Http\Controllers\PaymentController::class, 'due'])->name('payments.due');
Route::get('/payments/completed', [App\Http\Controllers\PaymentController::class, 'completed'])->name('payments.completed');
Route::get('/payments/generate', [App\Http\Controllers\PaymentController::class, 'generate'])->name('payments.generate');
Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
Route::get('/payments/{payment}', [App\Http\Controllers\PaymentController::class, 'show'])->name('payments.show');
Route::get('/payments/{payment}/edit', [App\Http\Controllers\PaymentController::class, 'edit'])->name('payments.edit');
Route::put('/payments/{payment}', [App\Http\Controllers\PaymentController::class, 'update'])->name('payments.update');
Route::get('/payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');


Route::get('/consumables', [App\Http\Controllers\ConsumableController::class, 'index'])->name('consumables.index');
Route::post('/consumables', [App\Http\Controllers\ConsumableController::class, 'store'])->name('consumables.store');
Route::get('/consumables/create', [App\Http\Controllers\ConsumableController::class, 'create'])->name('consumables.create');
Route::get('/consumables/{consumable}', [App\Http\Controllers\ConsumableController::class, 'show'])->name('consumables.show');
Route::get('/consumables/{consumable}/edit', [App\Http\Controllers\ConsumableController::class, 'edit'])->name('consumables.edit');
Route::put('/consumables/{consumable}', [App\Http\Controllers\ConsumableController::class, 'update'])->name('consumables.update');


Route::get('/consumables/{consumable}/addEntry', [App\Http\Controllers\ConsumableController::class, 'addEntry'])->name('consumables.addEntry');
Route::put('/consumables/{consumable}/updateEntry', [App\Http\Controllers\ConsumableController::class, 'updateEntry'])->name('consumables.updateEntry');

Route::get('/stock', [App\Http\Controllers\EntryController::class, 'index'])->name('entries.index');
Route::get('/stock/purchase', [App\Http\Controllers\EntryController::class, 'purchase'])->name('entries.purchase');
Route::get('/stock/issue', [App\Http\Controllers\EntryController::class, 'issue'])->name('entries.issue');
Route::get('/stock/{consumable}', [App\Http\Controllers\EntryController::class, 'consumable_all'])->name('entries.consumable_all');
Route::get('/stock/{consumable}/purchase', [App\Http\Controllers\EntryController::class, 'consumable_purchase'])->name('entries.consumable_purchase');
Route::get('/stock/{consumable}/issue', [App\Http\Controllers\EntryController::class, 'consumable_issue'])->name('entries.consumable_issue');



Route::get('/officials', [App\Http\Controllers\OfficialController::class, 'index'])->name('officials.index');
Route::post('/officials', [App\Http\Controllers\OfficialController::class, 'store'])->name('officials.store');
Route::get('/officials/{official}', [App\Http\Controllers\OfficialController::class, 'show'])->name('officials.show');
Route::get('/officials/{official}/edit', [App\Http\Controllers\OfficialController::class, 'edit'])->name('officials.edit');
Route::put('/officials/{official}', [App\Http\Controllers\OfficialController::class, 'update'])->name('officials.update');
Route::get('/officials/create', [App\Http\Controllers\OfficialController::class, 'create'])->name('officials.create');


Route::get('/assets/servers', [App\Http\Controllers\ServerController::class, 'index'])->name('servers.index');
Route::post('/assets/servers', [App\Http\Controllers\ServerController::class, 'store'])->name('servers.store');
Route::get('/assets/servers/{server}', [App\Http\Controllers\ServerController::class, 'show'])->name('servers.show');
Route::get('/assets/servers/{server}/edit', [App\Http\Controllers\ServerController::class, 'edit'])->name('servers.edit');
Route::put('/assets/servers/{server}', [App\Http\Controllers\ServerController::class, 'update'])->name('servers.update');
Route::get('/assets/servers/create', [App\Http\Controllers\ServerController::class, 'create'])->name('servers.create');

Route::get('/assets/switches', [App\Http\Controllers\DeviceController::class, 'index'])->name('switches.index');
Route::post('/assets/switches', [App\Http\Controllers\DeviceController::class, 'store'])->name('switches.store');
Route::get('/assets/switches/{device}', [App\Http\Controllers\DeviceController::class, 'show'])->name('switches.show');
Route::get('/assets/switches/{device}/edit', [App\Http\Controllers\DeviceController::class, 'edit'])->name('switches.edit');
Route::put('/assets/switches/{device}', [App\Http\Controllers\DeviceController::class, 'update'])->name('switches.update');
Route::get('/assets/switches/create', [App\Http\Controllers\DeviceController::class, 'create'])->name('switches.create');

Route::get('/assets/laptops', [App\Http\Controllers\LaptopController::class, 'index'])->name('laptops.index');
Route::get('/assets/desktops', [App\Http\Controllers\DesktopController::class, 'index'])->name('desktops.index');
Route::get('/assets/storage', [App\Http\Controllers\StorageController::class, 'index'])->name('storages.index');
Route::get('/officials', [App\Http\Controllers\OfficialController::class, 'index'])->name('officials.index');
Route::get('/locations', [App\Http\Controllers\LocationController::class, 'index'])->name('locations.index');
Route::get('/manufacturers', [App\Http\Controllers\ManufacturerController::class, 'index'])->name('manufacturers.index');


Route::get('/tasks', [App\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
Route::get('/tasks/pending', [App\Http\Controllers\TaskController::class, 'pending'])->name('tasks.pending');
Route::get('/tasks/completed', [App\Http\Controllers\TaskController::class, 'completed'])->name('tasks.completed');
Route::post('/tasks', [App\Http\Controllers\TaskController::class, 'store'])->name('tasks.store');
Route::get('/tasks/{task}', [App\Http\Controllers\TaskController::class, 'show'])->name('tasks.show');
Route::get('/tasks/{task}/edit', [App\Http\Controllers\TaskController::class, 'edit'])->name('tasks.edit');
Route::put('/tasks/{task}', [App\Http\Controllers\TaskController::class, 'update'])->name('tasks.update');
Route::get('/tasks/create', [App\Http\Controllers\TaskController::class, 'create'])->name('tasks.create');

Route::get('/developers', [App\Http\Controllers\DeveloperController::class, 'index'])->name('developers.index');
Route::get('/developers/active', [App\Http\Controllers\DeveloperController::class, 'active'])->name('developers.active');
Route::get('/developers/discontinued', [App\Http\Controllers\DeveloperController::class, 'discontinued'])->name('developers.discontinued');
Route::post('/developers', [App\Http\Controllers\DeveloperController::class, 'store'])->name('developers.store');
Route::get('/developers/{developer}', [App\Http\Controllers\DeveloperController::class, 'show'])->name('developers.show');
Route::get('/developers/{developer}/edit', [App\Http\Controllers\DeveloperController::class, 'edit'])->name('developers.edit');
Route::put('/developers/{consumable}', [App\Http\Controllers\DeveloperController::class, 'update'])->name('developers.update');
Route::get('/developers/create', [App\Http\Controllers\DeveloperController::class, 'create'])->name('developers.create');


