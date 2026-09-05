@props(['name', 'size' => '5'])
<svg {{ $attributes->merge(['class' => "h-{$size} w-{$size}"]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('dashboard') <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/> @break
        @case('report') <path d="M3 3v18h18"/><path d="m7 16 4-5 4 3 5-7"/> @break
        @case('account_balance_wallet')
        @case('wallet') <path d="M4 6.5h14.5A1.5 1.5 0 0 1 20 8v10a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h12"/><path d="M15 12h5v4h-5a2 2 0 0 1 0-4Z"/> @break
        @case('account_balance')
        @case('bank') <path d="m3 9 9-5 9 5"/><path d="M5 10h14"/><path d="M6.5 10v7M10.25 10v7M13.75 10v7M17.5 10v7"/><path d="M4 17h16M3 20h18"/> @break
        @case('credit_card')
        @case('card') <rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/> @break
        @case('payments')
        @case('cash') <rect width="20" height="13" x="2" y="5.5" rx="2"/><circle cx="12" cy="12" r="2.75"/><path d="M6 8.5A2.5 2.5 0 0 1 3.5 11M18 15.5a2.5 2.5 0 0 1 2.5-2.5"/> @break
        @case('savings') <path d="M5 12a7 6 0 0 1 13.2-2.8H21v5h-2.2a7 6 0 0 1-2.3 2.5V20h-3v-2H9v2H6v-3a6 6 0 0 1-1-5Z"/><path d="M8.5 8.2A4.5 4.5 0 0 1 13 5.5c1 0 2 .3 2.8.8"/><path d="M14 9h.01M3 10v3"/> @break
        @case('phone_android')
        @case('ewallet') <rect width="12" height="20" x="6" y="2" rx="2"/><path d="M9 5h6M10 18h4"/> @break
        @case('monitoring')
        @case('investment') <path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 2 5-6"/><path d="M16 7h3v3"/> @break
        @case('more_horiz')
        @case('other') <circle cx="5" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.5" fill="currentColor" stroke="none"/> @break
        @case('category') <path d="M20 12V7H4v5"/><path d="M4 17h16"/><path d="M8 7V4h8v3"/><rect width="6" height="5" x="3" y="12" rx="1"/><rect width="6" height="5" x="15" y="12" rx="1"/> @break
        @case('transaction') <path d="m7 7 10 10"/><path d="M17 7v10H7"/> @break
        @case('transfer') <path d="m17 3 4 4-4 4"/><path d="M3 7h18"/><path d="m7 21-4-4 4-4"/><path d="M21 17H3"/> @break
        @case('budget') <path d="M21 12a9 9 0 1 1-9-9v9z"/><path d="M12 3a9 9 0 0 1 9 9h-9z"/> @break
        @case('bill') <path d="M6 2v4"/><path d="M18 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 15h2"/><path d="M14 15h2"/> @break
        @case('debt') <path d="M7 11h10"/><path d="M7 15h6"/><path d="M9 3h6l4 4v14H5V3z"/><path d="M14 3v5h5"/> @break
        @case('goal') <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/> @break
        @case('settings') <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.6v-.1A1.7 1.7 0 0 0 8 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 3.6 15a1.7 1.7 0 0 0-1.6-1H2V10h.1A1.7 1.7 0 0 0 3.6 8a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8 3.6a1.7 1.7 0 0 0 1-1.6V2h4v.1A1.7 1.7 0 0 0 15 3.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 8a1.7 1.7 0 0 0 1.6 1.6h.1v4H21a1.7 1.7 0 0 0-1.6 1.4z"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/> @break
        @case('logout') <path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('more') <circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('chevron') <path d="m9 18 6-6-6-6"/> @break
        @case('close') <path d="M18 6 6 18M6 6l12 12"/> @break
        @case('check') <path d="M20 6 9 17l-5-5"/> @break
        @case('alert') <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/> @break
        @case('trash') <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/> @break
        @case('calendar') <rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/> @break
        @case('folder') <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/> @break
        @case('database') <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/> @break
        @case('cloud-arrow-down') <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><path d="m8 17 4 4 4-4"/> @break
        @case('cloud-arrow-up') <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 21v-9"/><path d="m8 15 4-4 4 4"/> @break
        @default <circle cx="12" cy="12" r="9"/><path d="M12 8v4l2 2"/>
    @endswitch
</svg>
