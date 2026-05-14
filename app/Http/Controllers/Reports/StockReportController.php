<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockReportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 5 — CURRENT STOCK
    // ──────────────────────────────────────────────────────────────────────────

    public function current(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $categories = Category::orderBy('cat_name')->get();
        $brCode     = $request->get('br_code',  session('br_code'));
        $catCode    = $request->get('cat_code');
        $showZero   = $request->has('show') ? $request->boolean('show_zero') : false;
        $showReport = $request->has('show');

        $catGroups = [];
        $error     = null;

        if ($showReport) {
            $rows = $this->getCurrentRows($brCode, $catCode, $showZero);
            foreach ($rows as $row) {
                $catGroups[$row->cat_name][] = $row;
            }
        }

        return view('reports.stock.current', compact(
            'branches', 'categories',
            'brCode', 'catCode', 'showZero',
            'showReport', 'catGroups', 'error'
        ));
    }

    public function currentExport(Request $request)
    {
        $brCode   = $request->get('br_code');
        $catCode  = $request->get('cat_code');
        $showZero = $request->boolean('show_zero');

        $rows = $this->getCurrentRows($brCode, $catCode, $showZero);

        $headings = ['Mat Code', 'Item Name', 'Category', 'UOM', 'Opening', 'Receipts', 'Issues', 'Closing'];
        $data     = [];

        $catGroups = [];
        foreach ($rows as $row) {
            $catGroups[$row->cat_name][] = $row;
        }

        $grandOb = $grandRcpts = $grandIssues = $grandCl = 0.0;
        foreach ($catGroups as $catName => $items) {
            $cOb = $cRcpts = $cIssues = $cCl = 0.0;
            foreach ($items as $item) {
                $cl = $item->ob + $item->rcpts - $item->issues;
                $data[] = [
                    $item->mat_code, $item->mat_name, $catName, $item->uom_name,
                    indianFmt($item->ob, 3), indianFmt($item->rcpts, 3),
                    indianFmt($item->issues, 3), indianFmt($cl, 3),
                ];
                $cOb += $item->ob; $cRcpts += $item->rcpts;
                $cIssues += $item->issues; $cCl += $cl;
            }
            $data[] = ['', 'Category Total: ' . $catName, '', '',
                indianFmt($cOb, 3), indianFmt($cRcpts, 3), indianFmt($cIssues, 3), indianFmt($cCl, 3)];
            $grandOb += $cOb; $grandRcpts += $cRcpts;
            $grandIssues += $cIssues; $grandCl += $cCl;
        }
        if (!empty($catGroups)) {
            $data[] = ['', 'Grand Total', '', '',
                indianFmt($grandOb, 3), indianFmt($grandRcpts, 3),
                indianFmt($grandIssues, 3), indianFmt($grandCl, 3)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'current-stock-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function getCurrentRows($brCode, $catCode, bool $showZero)
    {
        return DB::table('stock as s')
            ->join('products as pr',  's.mat_code',  '=', 'pr.mat_code')
            ->join('categories as c', 's.cat_code',  '=', 'c.cat_code')
            ->join('uoms as u',       'pr.uom',      '=', 'u.uom_code')
            ->select([
                's.mat_code', 'pr.mat_name', 'c.cat_name', 'u.uom_name',
                's.ob', 's.rcpts', 's.issues',
            ])
            ->when($brCode,       fn($q) => $q->where('s.br_code',  $brCode))
            ->when($catCode,      fn($q) => $q->where('s.cat_code', $catCode))
            ->when(!$showZero,    fn($q) => $q->whereRaw('(s.ob + s.rcpts - s.issues) > 0'))
            ->orderBy('c.cat_name')
            ->orderBy('pr.mat_name')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REPORT 6 — STOCK CLOSING (AS-ON-DATE)
    // ──────────────────────────────────────────────────────────────────────────

    public function closing(Request $request)
    {
        $branches   = Branch::orderBy('br_name')->get();
        $categories = Category::orderBy('cat_name')->get();
        $asOnDate   = $request->get('as_on_date', now()->format('Y-m-d'));
        $brCode     = $request->get('br_code',    session('br_code'));
        $catCode    = $request->get('cat_code');
        $showReport = $request->has('show');

        $catGroups = [];
        $error     = null;

        if ($showReport) {
            $rows = $this->getClosingRows($asOnDate, $brCode, $catCode);
            foreach ($rows as $row) {
                $row->closing = $row->ob + $row->receipts - $row->issues;
                $catGroups[$row->cat_name][] = $row;
            }
        }

        return view('reports.stock.closing', compact(
            'branches', 'categories',
            'asOnDate', 'brCode', 'catCode',
            'showReport', 'catGroups', 'error'
        ));
    }

    public function closingExport(Request $request)
    {
        $asOnDate = $request->get('as_on_date', now()->format('Y-m-d'));
        $brCode   = $request->get('br_code');
        $catCode  = $request->get('cat_code');

        $rows = $this->getClosingRows($asOnDate, $brCode, $catCode);

        $headings = ['Mat Code', 'Item Name', 'Category', 'UOM', 'Opening', 'Receipts', 'Issues', 'Closing'];
        $data     = [];

        $catGroups = [];
        foreach ($rows as $row) {
            $row->closing = $row->ob + $row->receipts - $row->issues;
            $catGroups[$row->cat_name][] = $row;
        }

        $grandOb = $grandRcpts = $grandIssues = $grandCl = 0.0;
        foreach ($catGroups as $catName => $items) {
            $cOb = $cRcpts = $cIssues = $cCl = 0.0;
            foreach ($items as $item) {
                $data[] = [
                    $item->mat_code, $item->mat_name, $catName, $item->uom_name,
                    indianFmt($item->ob, 3), indianFmt($item->receipts, 3),
                    indianFmt($item->issues, 3), indianFmt($item->closing, 3),
                ];
                $cOb += $item->ob; $cRcpts += $item->receipts;
                $cIssues += $item->issues; $cCl += $item->closing;
            }
            $data[] = ['', 'Category Total: ' . $catName, '', '',
                indianFmt($cOb, 3), indianFmt($cRcpts, 3), indianFmt($cIssues, 3), indianFmt($cCl, 3)];
            $grandOb += $cOb; $grandRcpts += $cRcpts;
            $grandIssues += $cIssues; $grandCl += $cCl;
        }
        if (!empty($catGroups)) {
            $data[] = ['', 'Grand Total', '', '',
                indianFmt($grandOb, 3), indianFmt($grandRcpts, 3),
                indianFmt($grandIssues, 3), indianFmt($grandCl, 3)];
        }

        return Excel::download(
            new GenericExport($data, $headings),
            'stock-closing-as-on-' . $asOnDate . '.xlsx'
        );
    }

    private function getClosingRows(string $asOnDate, $brCode, $catCode)
    {
        return DB::table('stock as s')
            ->join('products as pr',  's.mat_code', '=', 'pr.mat_code')
            ->join('categories as c', 's.cat_code', '=', 'c.cat_code')
            ->join('uoms as u',       'pr.uom',     '=', 'u.uom_code')
            ->selectRaw("
                s.mat_code, pr.mat_name, c.cat_name, u.uom_name, s.ob,
                COALESCE((
                    SELECT SUM(pd.qty) FROM purchase_dtl pd
                    WHERE pd.mat_code = s.mat_code
                      AND pd.br_code  = s.br_code
                      AND pd.inv_date <= ?
                ), 0) as receipts,
                COALESCE((
                    SELECT SUM(sd.qty) FROM sale_dtl sd
                    WHERE sd.mat_code = s.mat_code
                      AND sd.br_code  = s.br_code
                      AND sd.inv_date <= ?
                ), 0) as issues
            ", [$asOnDate, $asOnDate])
            ->when($brCode,  fn($q) => $q->where('s.br_code',  $brCode))
            ->when($catCode, fn($q) => $q->where('s.cat_code', $catCode))
            ->orderBy('c.cat_name')
            ->orderBy('pr.mat_name')
            ->get();
    }
}
