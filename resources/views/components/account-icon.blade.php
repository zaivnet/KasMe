@props(['icon' => null, 'type' => null, 'size' => '5'])
@php
    $aliases = [
        'wallet' => 'wallet',
        'account_balance_wallet' => 'wallet',
        'bank' => 'bank',
        'account_balance' => 'bank',
        'card' => 'card',
        'credit_card' => 'card',
        'cash' => 'cash',
        'payments' => 'cash',
        'savings' => 'savings',
        'ewallet' => 'ewallet',
        'phone_android' => 'ewallet',
        'investment' => 'investment',
        'monitoring' => 'investment',
        'other' => 'other',
        'more_horiz' => 'other',
    ];

    $typeFallbacks = [
        'cash' => 'cash',
        'bank' => 'bank',
        'ewallet' => 'ewallet',
        'savings' => 'savings',
        'credit_card' => 'card',
        'other' => 'other',
    ];

    $storedIcon = is_string($icon) ? trim($icon) : '';
    $resolvedIcon = $aliases[$storedIcon] ?? $typeFallbacks[$type] ?? 'wallet';
@endphp

<x-icon :name="$resolvedIcon" :size="$size" data-account-icon="{{ $resolvedIcon }}" {{ $attributes }} />
