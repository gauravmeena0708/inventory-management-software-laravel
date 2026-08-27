<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Consumable;
use App\Models\Entry;
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
        abort_unless($user, 403);

        $visibleAssets = Asset::query()->visibleTo($user);
        $visibleAgreements = Agreement::query()->visibleTo($user);
        $visiblePayments = Payment::query()->visibleTo($user);

        $kpis = [
            'total_assets' => (clone $visibleAssets)->count(),
            'assets_in_use' => (clone $visibleAssets)->inUse()->count(),
            'assets_in_stock' => (clone $visibleAssets)->inStock()->count(),
            'assets_decommissioned' => (clone $visibleAssets)->decommissioned()->count(),
            'assets_by_type' => (clone $visibleAssets)
                ->selectRaw('asset_type, count(*) as total')
                ->groupBy('asset_type')
                ->pluck('total', 'asset_type')
                ->toArray(),
            'agreements_expiring_30_days' => (clone $visibleAgreements)->expiringSoon(30)->count(),
            'agreements_expiring_180_days' => (clone $visibleAgreements)->expiringSoon(180)->count(),
            'agreements_expired' => (clone $visibleAgreements)->expired()->count(),
            'total_agreements' => (clone $visibleAgreements)->count(),
            'low_stock_consumables' => Consumable::lowStock()->count(),
            'total_consumables' => Consumable::count(),
            'pending_payments' => (clone $visiblePayments)->pending()->count(),
            'overdue_payments' => (clone $visiblePayments)->overdue()->count(),
            'completed_payments' => (clone $visiblePayments)->completed()->count(),
            'recent_activities' => $user->canViewAuditHistory()
                ? Activity::query()
                    ->with('causer')
                    ->where('subject_type', Asset::class)
                    ->whereIn('subject_id', Asset::query()->visibleTo($user)->select('assets.id'))
                    ->latest()
                    ->take(10)
                    ->get()
                : collect(),
        ];

        if ($request->wantsJson()) {
            return response()->json($kpis);
        }

        if (config('inventory.poc_ui_mode')) {
            $kpis['recent_assignments'] = AssetAssignment::query()
                ->whereHas('asset', fn ($query) => $query->visibleTo($user))
                ->with([
                    'asset',
                    'official' => fn ($query) => $query->visibleTo($user),
                ])
                ->latest('assigned_at')
                ->take(6)
                ->get();

            $kpis['recent_stock_entries'] = Entry::query()
                ->with([
                    'consumable',
                    'recipient' => fn ($query) => $query->visibleTo($user),
                    'recorder',
                ])
                ->latest('id')
                ->take(6)
                ->get();
        }

        if (view()->exists('dashboard')) {
            return view('dashboard', $kpis);
        }

        return view('home', $kpis);
    }
}
