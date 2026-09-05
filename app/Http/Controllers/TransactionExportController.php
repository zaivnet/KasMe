<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionExportRequest;
use App\Models\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExportController extends Controller
{
    public function __invoke(TransactionExportRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $query = $request->user()->transactions()->with(['account', 'category'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '<=', $date))
            ->when($filters['account_id'] ?? null, fn ($query, $id) => $query->where('account_id', $id))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Jenis', 'Arah penyesuaian', 'Akun', 'Kategori', 'Mata uang', 'Jumlah', 'Deskripsi', 'Ada lampiran']);

            foreach ($query->lazyById(500) as $transaction) {
                fputcsv($output, [
                    $transaction->transaction_date->format('Y-m-d'),
                    Transaction::TYPES[$transaction->type],
                    $transaction->adjustment_direction ? Transaction::ADJUSTMENT_DIRECTIONS[$transaction->adjustment_direction] : '',
                    $transaction->account->name,
                    $transaction->category?->name ?? '',
                    $transaction->account->currency,
                    $transaction->amount,
                    $transaction->description ?? '',
                    $transaction->attachment ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($output);
        }, 'transaksi-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
