<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonthlyCashSettlementRequest;
use App\Models\MonthlyCashSettlement;
use App\Services\MonthlyCashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MonthlyCashSettlementController extends Controller
{
    public function __construct(
        private MonthlyCashService $monthlyCashService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $summary = $this->monthlyCashService->summaryForDashboard($user);

        $settlements = $user->monthlyCashSettlements()
            ->orderByDesc('period')
            ->paginate(15);

        // Attach discrepancy & historical audit information
        $settlements->getCollection()->transform(function (MonthlyCashSettlement $settlement) {
            $settlement->historicalDiscrepancy = $this->monthlyCashService->detectDiscrepancy($settlement);
            return $settlement;
        });

        return view('settlements.index', [
            'settlements' => $settlements,
            'summary' => $summary,
            'currency' => $user->setting?->currency ?: 'IDR',
        ]);
    }

    public function store(StoreMonthlyCashSettlementRequest $request): RedirectResponse
    {
        $settlement = $this->monthlyCashService->settle($request->user(), $request->validated());

        return redirect()->route('settlements.index')->with(
            'success',
            "Setoran kas periode {$settlement->periodLabel()} berhasil dicatat."
        );
    }

    public function show(Request $request, MonthlyCashSettlement $settlement): View
    {
        Gate::authorize('view', $settlement);

        $historicalDiscrepancy = $this->monthlyCashService->detectDiscrepancy($settlement);

        return view('settlements.show', [
            'settlement' => $settlement,
            'historicalDiscrepancy' => $historicalDiscrepancy,
            'currency' => $request->user()->setting?->currency ?: 'IDR',
        ]);
    }

    public function setBaseline(Request $request): RedirectResponse
    {
        $user = $request->user();
        $timezone = $user->setting?->timezone ?: config('app.timezone', 'Asia/Jakarta');
        $currentMonthStart = \Carbon\CarbonImmutable::now($timezone)->startOfMonth();

        $validated = $request->validate([
            'cash_accountability_start_period' => ['required', 'date'],
        ], [
            'cash_accountability_start_period.required' => 'Periode mulai pertanggungjawaban kas wajib dipilih.',
            'cash_accountability_start_period.date' => 'Format periode tidak valid.',
        ]);

        $startPeriod = \Carbon\CarbonImmutable::parse($validated['cash_accountability_start_period'], $timezone)->startOfMonth();

        // Rule: Baseline cannot be in the future relative to current month
        if ($startPeriod->gt($currentMonthStart)) {
            return back()->withErrors([
                'cash_accountability_start_period' => 'Periode awal pertanggungjawaban tidak boleh melampaui bulan berjalan.',
            ]);
        }

        // Rule: Baseline modification safety with existing settlements
        // If user already has active settlements, baseline cannot be moved to AFTER the earliest active settlement
        $earliestActiveSettlement = $user->monthlyCashSettlements()
            ->active()
            ->orderBy('period')
            ->first();

        if ($earliestActiveSettlement) {
            $earliestPeriod = \Carbon\CarbonImmutable::parse($earliestActiveSettlement->period)->startOfMonth();
            if ($startPeriod->gt($earliestPeriod)) {
                return back()->withErrors([
                    'cash_accountability_start_period' => "Periode awal tidak boleh lebih baru dari setoran aktif pertama ({$earliestActiveSettlement->periodLabel()}).",
                ]);
            }
        }

        $user->setting()->updateOrCreate([], [
            'cash_accountability_start_period' => $startPeriod->toDateString(),
        ]);

        return back()->with(
            'success',
            'Pertanggungjawaban kas berhasil diaktifkan mulai periode ' . $startPeriod->locale('id')->translatedFormat('F Y') . '.'
        );
    }

    public function destroy(Request $request, MonthlyCashSettlement $settlement): RedirectResponse
    {
        Gate::authorize('delete', $settlement);

        $validated = $request->validate([
            'void_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $periodLabel = $settlement->periodLabel();

        // Delegate void to service: wraps operation in DB::transaction with the
        // same user-row lock used by settle(), preventing settle/void races.
        $this->monthlyCashService->voidSettlement(
            $request->user(),
            $settlement,
            $validated['void_reason'] ?? ''
        );

        return redirect()->route('settlements.index')->with(
            'success',
            "Catatan setoran periode {$periodLabel} berhasil dibatalkan."
        );
    }
}
