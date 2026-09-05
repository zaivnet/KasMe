<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Transaction;
use App\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __invoke(ReportRequest $request, ReportService $reports): View
    {
        $filters = $request->validated();

        return view('reports.index', array_merge($reports->forUser($request->user(), $filters), [
            'filters' => $filters,
            'accounts' => $request->user()->accounts()->orderBy('name')->get(),
            'categories' => $request->user()->categories()->orderBy('name')->get(),
            'types' => Transaction::TYPES,
        ]));
    }

    public function export(ReportRequest $request, ReportService $reports): StreamedResponse
    {
        $query = $reports->exportQuery($request->user(), $request->validated())
            ->with(['account', 'category'])->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Jenis', 'Akun', 'Kategori', 'Jumlah', 'Deskripsi']);
            foreach ($query->lazyById(500, 'transactions.id', 'id') as $transaction) {
                fputcsv($output, [$transaction->transaction_date->format('Y-m-d'), Transaction::TYPES[$transaction->type], $transaction->account->name, $transaction->category?->name ?? '', $transaction->amount, $transaction->description ?? '']);
            }
            fclose($output);
        }, 'laporan-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
