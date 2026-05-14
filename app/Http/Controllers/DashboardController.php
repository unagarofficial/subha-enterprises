<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $brCode    = (int) session('br_code');
        $finYearId = (int) session('fin_year_id');
        $loginDate = session('login_date'); // Y-m-d

        // ── Row 1: Today's Summary ────────────────────────────────────────────

        $todaySales = DB::table('sale_hdr')
            ->where('br_code', $brCode)
            ->where('inv_date', $loginDate)
            ->where('fin_year_id', $finYearId)
            ->selectRaw('COALESCE(SUM(nett), 0) as total, COUNT(*) as cnt')
            ->first();

        $todayPurchases = DB::table('purchase_hdr')
            ->where('br_code', $brCode)
            ->where('inv_date', $loginDate)
            ->where('fin_year_id', $finYearId)
            ->selectRaw('COALESCE(SUM(nett), 0) as total, COUNT(*) as cnt')
            ->first();

        $lowStockCount = DB::table('stock')
            ->where('br_code', $brCode)
            ->where('cl_stock', '<=', 10)
            ->count();

        // ── Row 2: Monthly Sales Chart (last 30 days) ────────────────────────

        $today    = Carbon::parse($loginDate);
        $from30   = $today->copy()->subDays(29)->format('Y-m-d');
        $to30     = $today->format('Y-m-d');

        $dailySales = DB::table('sale_hdr')
            ->where('br_code', $brCode)
            ->where('fin_year_id', $finYearId)
            ->whereBetween('inv_date', [$from30, $to30])
            ->selectRaw('inv_date, COALESCE(SUM(nett), 0) as total')
            ->groupBy('inv_date')
            ->orderBy('inv_date')
            ->get()
            ->keyBy('inv_date');

        $chartLabels = [];
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date          = $today->copy()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::parse($date)->format('d M');
            $chartValues[] = isset($dailySales[$date]) ? (float) $dailySales[$date]->total : 0;
        }

        // ── Row 2: Top 5 Customers this month ───────────────────────────────

        $monthStart = $today->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd   = $today->copy()->endOfMonth()->format('Y-m-d');

        $topCustomers = DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.fin_year_id', $finYearId)
            ->whereBetween('h.inv_date', [$monthStart, $monthEnd])
            ->selectRaw('h.party_code, p.party_name, COALESCE(SUM(h.nett), 0) as total, COUNT(*) as bill_count')
            ->groupBy('h.party_code', 'p.party_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ── Row 4: Recent Transactions ───────────────────────────────────────

        $recentSales = DB::table('sale_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.fin_year_id', $finYearId)
            ->select('h.inv_no', 'h.inv_date', 'h.nett', 'h.is_locked', 'h.sale_type', 'h.bill_type',
                     'p.party_name')
            ->orderByDesc('h.inv_date')
            ->orderByDesc('h.inv_no')
            ->limit(10)
            ->get();

        $recentPurchases = DB::table('purchase_hdr as h')
            ->join('parties as p', 'h.party_code', '=', 'p.party_code')
            ->where('h.br_code', $brCode)
            ->where('h.fin_year_id', $finYearId)
            ->select('h.inv_no', 'h.inv_date', 'h.nett', 'p.party_name')
            ->orderByDesc('h.inv_date')
            ->orderByDesc('h.inv_no')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'todaySales', 'todayPurchases', 'lowStockCount',
            'chartLabels', 'chartValues',
            'topCustomers',
            'recentSales', 'recentPurchases',
            'loginDate'
        ));
    }
}
