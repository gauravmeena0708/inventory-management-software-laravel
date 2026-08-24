<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request to display the dashboard.
     */
    public function __invoke(Request $request): View|JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Compute KPIs and render dashboard.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $kpis = [
            'total_assets' => Asset::count(),
            'assets_in_use' => Asset::inUse()->count(),
            'assets_in_stock' => Asset::inStock()->count(),
            'assets_decommissioned' => Asset::decommissioned()->count(),
            'assets_by_type' => Asset::query()
                ->selectRaw('asset_type, count(*) as total')
                ->groupBy('asset_type')
                ->pluck('total', 'asset_type')
                ->toArray(),
            'agreements_expiring_30_days' => Agreement::expiringSoon(30)->count(),
            'agreements_expiring_180_days' => Agreement::expiringSoon(180)->count(),
            'agreements_expired' => Agreement::expired()->count(),
            'total_agreements' => Agreement::count(),
            'low_stock_consumables' => Consumable::lowStock()->count(),
            'total_consumables' => Consumable::count(),
            'pending_payments' => Payment::pending()->count(),
            'overdue_payments' => Payment::overdue()->count(),
            'completed_payments' => Payment::completed()->count(),
            'recent_activities' => ($user && $user->canViewAuditHistory())
                ? Activity::with('causer')->latest()->take(10)->get()
                : collect(),
        ];

        if ($request->wantsJson()) {
            return response()->json($kpis);
        }

        if (view()->exists('dashboard')) {
            return view('dashboard', $kpis);
        }

        return view('home', $kpis);
    }
}
