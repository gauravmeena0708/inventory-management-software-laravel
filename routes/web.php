<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AssetAssignmentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetPlacementController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ConsumableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\FileRecordController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationStockController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\OrganizationalContextController;
use App\Http\Controllers\OrganizationalHierarchyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SpatialMapController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        if (view()->exists('auth.login')) {
            return view('auth.login');
        }

        return response('<h1>Login</h1><form method="POST" action="/login"><input type="email" name="email" placeholder="Email"><input type="password" name="password" placeholder="Password"><button type="submit">Login</button></form>');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

// Authenticated Modern Application Routes
Route::middleware('auth')->group(function () {
    Route::post('organizational-context', [OrganizationalContextController::class, 'update'])
        ->name('organizational-context.update');
    Route::get('organization/hierarchy', [OrganizationalHierarchyController::class, 'index'])
        ->name('organization.hierarchy');

    // Dashboard
    Route::get('/', DashboardController::class)->name('home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Assets & Assignments
    Route::get('assets/export', [AssetController::class, 'export'])->name('assets.export');
    Route::post('assets/exports', [AssetController::class, 'export'])->name('assets.exports.store');
    Route::resource('assets', AssetController::class);
    Route::post('assets/{asset}/placements', [AssetPlacementController::class, 'store'])
        ->name('assets.placements.store');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::post('assets/{asset}/assign', [AssetAssignmentController::class, 'assign'])->name('assets.assign');
    Route::post('assets/{asset}/return', [AssetAssignmentController::class, 'return'])->name('assets.return');
    Route::patch('assets/{asset}/decommission', [AssetAssignmentController::class, 'decommission'])->name('assets.decommission');
    Route::post('assets/{asset}/decommission', [AssetAssignmentController::class, 'decommission'])->name('assets.decommission.post');

    // Consumables & Stock Ledger
    Route::resource('consumables', ConsumableController::class);
    Route::post('consumables/{consumable}/entries', [EntryController::class, 'store'])->name('consumables.entries.store');
    Route::get('stock', [EntryController::class, 'index'])->name('stock.index');
    Route::get('stock/entries', [EntryController::class, 'index'])->name('entries.index');
    Route::post('stock/entries', [EntryController::class, 'store'])->name('stock.entries.store');
    Route::post('entries', [EntryController::class, 'store'])->name('entries.store');
    Route::get('stock/locations', [LocationStockController::class, 'index'])->name('stock.locations.index');
    Route::post('stock/purchases', [LocationStockController::class, 'purchase'])->name('stock.purchases.store');
    Route::post('stock/issues', [LocationStockController::class, 'issue'])->name('stock.issues.store');
    Route::post('stock/adjustments', [LocationStockController::class, 'adjust'])->name('stock.adjustments.store');
    Route::post('stock/transfers', [LocationStockController::class, 'transfer'])->name('stock.transfers.store');

    // Vendor Agreements
    Route::get('agreements/export', [AgreementController::class, 'export'])->name('agreements.export');
    Route::post('agreements/exports', [AgreementController::class, 'export'])->name('agreements.exports.store');
    Route::resource('agreements', AgreementController::class);

    // Payments
    Route::get('payments/due', [PaymentController::class, 'due'])->name('payments.due');
    Route::get('payments/completed', [PaymentController::class, 'completed'])->name('payments.completed');
    Route::post('payments/{payment}/complete', [PaymentController::class, 'complete'])->name('payments.complete');
    Route::patch('payments/{payment}/complete', [PaymentController::class, 'complete'])->name('payments.complete.patch');
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel.patch');
    Route::resource('payments', PaymentController::class);

    // Personnel & Master Data
    Route::resource('officials', OfficialController::class);
    Route::get('developers/active', [DeveloperController::class, 'active'])->name('developers.active');
    Route::get('developers/discontinued', [DeveloperController::class, 'discontinued'])->name('developers.discontinued');
    Route::resource('developers', DeveloperController::class);
    Route::get('tasks/pending', [TaskController::class, 'pending'])->name('tasks.pending');
    Route::get('tasks/completed', [TaskController::class, 'completed'])->name('tasks.completed');
    Route::resource('tasks', TaskController::class);
    Route::resource('files', FileRecordController::class);
    Route::resource('locations', LocationController::class);
    Route::post('locations/{location}/spatial-maps', [SpatialMapController::class, 'store'])
        ->name('locations.spatial-maps.store');
    Route::get('spatial-maps/{spatialMap}', [SpatialMapController::class, 'show'])
        ->name('spatial-maps.show');
    Route::get('spatial-maps/{spatialMap}/content', [SpatialMapController::class, 'content'])
        ->name('spatial-maps.content');
    Route::get('spatial-maps/{spatialMap}/download', [SpatialMapController::class, 'download'])
        ->name('spatial-maps.download');
    Route::post('spatial-maps/{spatialMap}/supersede', [SpatialMapController::class, 'supersede'])
        ->name('spatial-maps.supersede');
    Route::delete('spatial-maps/{spatialMap}', [SpatialMapController::class, 'destroy'])
        ->name('spatial-maps.destroy');
    Route::patch('spatial-maps/{spatialMap}/assets/{asset}/placement', [SpatialMapController::class, 'relocate'])
        ->name('spatial-maps.assets.relocate');
    Route::resource('manufacturers', ManufacturerController::class);

    // Backward-Compatibility Category Route Aliases
    Route::get('assets/servers', fn () => redirect()->route('assets.index', ['type' => 'server']))->name('servers.index');
    Route::get('assets/laptops', fn () => redirect()->route('assets.index', ['type' => 'laptop']))->name('laptops.index');
    Route::get('assets/desktops', fn () => redirect()->route('assets.index', ['type' => 'desktop']))->name('desktops.index');
    Route::get('assets/switches', fn () => redirect()->route('assets.index', ['type' => 'switch']))->name('switches.index');
    Route::get('assets/storage', fn () => redirect()->route('assets.index', ['type' => 'storage']))->name('storages.index');
    Route::get('agreements/download', [AgreementController::class, 'export'])->name('agreements.download');
    Route::get('agreements/expired', fn () => redirect()->route('agreements.index', ['filter' => 'expired']))->name('agreements.expired');
    Route::get('agreements/due', fn () => redirect()->route('agreements.index', ['filter' => 'due']))->name('agreements.due');
});
