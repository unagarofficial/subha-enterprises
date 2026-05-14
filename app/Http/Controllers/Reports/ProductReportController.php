<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Firm;
use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductReportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // PRODUCT LIST
    // ──────────────────────────────────────────────────────────────────────────

    public function list(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $categories = Category::orderBy('cat_name')->get();
        $catCode    = $request->get('cat_code');
        $rateType   = $request->get('rate_type', 'sale_rate');
        $brCode     = $request->get('br_code', session('br_code'));
        $showReport = $request->has('show');

        $catGroups = [];
        if ($showReport) {
            $rows = $this->getProductRows($catCode, $brCode);
            foreach ($rows as $row) {
                $catGroups[$row->cat_name][] = $row;
            }
        }

        return view('reports.products.list', compact(
            'branches', 'categories', 'catCode', 'rateType', 'brCode',
            'showReport', 'catGroups'
        ));
    }

    public function listExport(Request $request)
    {
        $catCode  = $request->get('cat_code');
        $rateType = $request->get('rate_type', 'sale_rate');
        $brCode   = $request->get('br_code');

        $rows = $this->getProductRows($catCode, $brCode);

        if ($rateType === 'all') {
            $headings = ['Mat Code', 'Item Name', 'Category', 'UOM', 'Sale Rate', 'Y-Rate', 'B-Rate'];
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    $row->mat_code, $row->mat_name, $row->cat_name, $row->uom_name ?? '',
                    indianFmt($row->sale_rate), indianFmt($row->y_rate), indianFmt($row->b_rate),
                ];
            }
        } else {
            $rateLabel = match($rateType) {
                'y_rate' => 'Y-Rate',
                'b_rate' => 'B-Rate',
                default  => 'Sale Rate',
            };
            $headings = ['Mat Code', 'Item Name', 'Category', 'UOM', $rateLabel];
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    $row->mat_code, $row->mat_name, $row->cat_name, $row->uom_name ?? '',
                    indianFmt($row->$rateType),
                ];
            }
        }

        return Excel::download(new GenericExport($data, $headings), 'product-list.xlsx');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRICE LIST
    // ──────────────────────────────────────────────────────────────────────────

    public function priceList(Request $request)
    {
        $categories = Category::orderBy('cat_name')->get();
        $firm       = Firm::first();
        $catCode    = $request->get('cat_code');
        $rateType   = $request->get('rate_type', 'sale_rate');
        $showReport = $request->has('show');

        $catGroups = [];
        if ($showReport) {
            $rows = $this->getProductRows($catCode, null);
            foreach ($rows as $row) {
                $catGroups[$row->cat_name][] = $row;
            }
        }

        return view('reports.products.price-list', compact(
            'categories', 'catCode', 'rateType', 'showReport', 'catGroups', 'firm'
        ));
    }

    public function priceListExport(Request $request)
    {
        $catCode  = $request->get('cat_code');
        $rateType = $request->get('rate_type', 'sale_rate');

        $rows = $this->getProductRows($catCode, null);

        $rateLabel = match($rateType) {
            'y_rate' => 'Y-Rate',
            'b_rate' => 'B-Rate',
            'all'    => 'Sale / Y-Rate / B-Rate',
            default  => 'Sale Rate',
        };

        $headings = ['Category', 'Item Name', $rateLabel];
        $data = [];
        foreach ($rows as $row) {
            if ($rateType === 'all') {
                $rate = indianFmt($row->sale_rate) . ' / ' . indianFmt($row->y_rate) . ' / ' . indianFmt($row->b_rate);
            } else {
                $rate = indianFmt($row->$rateType);
            }
            $data[] = [$row->cat_name, $row->mat_name, $rate];
        }

        return Excel::download(new GenericExport($data, $headings), 'price-list.xlsx');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHARED QUERY
    // ──────────────────────────────────────────────────────────────────────────

    private function getProductRows($catCode, $brCode)
    {
        return DB::table('products as p')
            ->join('categories as c', 'p.cat_code', '=', 'c.cat_code')
            ->leftJoin('uoms as u', 'p.uom', '=', 'u.uom_code')
            ->select([
                'p.mat_code', 'p.mat_name', 'c.cat_name',
                'u.uom_name', 'p.sale_rate', 'p.y_rate', 'p.b_rate',
            ])
            ->when($catCode, fn($q) => $q->where('p.cat_code', $catCode))
            ->when($brCode,  fn($q) => $q->where('p.br_code',  $brCode))
            ->orderBy('c.cat_name')
            ->orderBy('p.mat_name')
            ->get();
    }
}
