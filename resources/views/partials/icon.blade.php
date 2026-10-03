<svg class="ht-icon" width="{{ $size ?? 20 }}" height="{{ $size ?? 20 }}" style="display:inline-block;vertical-align:-.15em;flex-shrink:0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
@case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
@case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/> @break
@case('bag') <path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/> @break
@case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
@case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
@case('external') <path d="M7 17 17 7M7 7h10v10"/> @break
@case('chevron') <path d="m7 10 5 5 5-5"/> @break
@case('flower') <path d="M12 12C4 10 3 3 7 3c3 0 5 5 5 9Zm0 0c8-2 9-9 5-9-3 0-5 5-5 9Zm0 0c-8-2-12 3-8 6 3 2 7-3 8-6Zm0 0c8-2 12 3 8 6-3 2-7-3-8-6Zm0 0c-5 6-3 10 0 10s5-4 0-10Z"/> @break
@case('shield') <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/> @break
@case('truck') <path d="M3 5h11v12H3V5Zm11 5h4l3 4v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/> @break
@case('gift') <path d="M3 8h18v4H3zM5 12v9h14v-9M12 8v13"/><path d="M12 8H8a3 3 0 1 1 3-3l1 3Zm0 0h4a3 3 0 1 0-3-3l-1 3Z"/> @break
@case('heart') <path d="M20.5 5.5a5 5 0 0 0-7 0L12 7l-1.5-1.5a5 5 0 0 0-7 7L12 21l8.5-8.5a5 5 0 0 0 0-7Z"/> @break
@case('mail') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/> @break
@case('lock') <rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 4v3"/> @break
@case('chat') <path d="M21 11a9 9 0 0 1-9 9H3l2-4a9 9 0 1 1 16-5Z"/><path d="M8 10h8m-8 4h5"/> @break
@case('check') <path d="m5 12 4 4L19 6"/> @break
@case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
@case('star') <path d="m12 3 2.8 5.7 6.3.9-4.6 4.4 1.1 6.3-5.6-3-5.6 3 1.1-6.3L2.9 9.6l6.3-.9Z"/> @break
@case('star-filled') <path fill="currentColor" d="m12 3 2.8 5.7 6.3.9-4.6 4.4 1.1 6.3-5.6-3-5.6 3 1.1-6.3L2.9 9.6l6.3-.9Z"/> @break
@case('bolt') <path d="m13 2-9 12h7l-1 8 10-12h-7Z"/> @break
@case('flame') <path d="M12 3c1 5 6 6 6 11a6 6 0 1 1-12 0c0-3 2-5 3-6 0 3 1 4 2 4 2-2 2-5 1-9Z"/> @break
@case('box') <path d="m12 3 9 5v9l-9 5-9-5V8Zm-9 5 9 5 9-5M12 13v9M7.5 5.5l9 5V15"/> @break
@case('leaf') <path d="M20 3C8 2 3 6 4 13c1 7 10 8 14 1 2-3 2-7 2-11ZM3 21 15 9"/> @break
@case('tree') <path d="m12 2 6 7h-3l5 7H4l5-7H6Zm0 14v6M9 22h6"/> @break
@case('citrus') <circle cx="12" cy="13" r="8"/><path d="M12 5V2m0 3c2-3 5-3 6-2M12 9v8m-4-4h8m-7-3 6 6m-6 0 6-6"/> @break
@case('fruit') <path d="M12 7c-8-4-11 5-7 11 2 4 5 3 7 2 2 1 5 2 7-2 4-6 1-15-7-11ZM12 7V3m0 2c2-3 4-3 6-2-1 3-3 4-6 2Z"/> @break
@case('waves') <path d="M2 6c3-4 5 4 8 0s5 4 8 0 4 0 4 0M2 12c3-4 5 4 8 0s5 4 8 0 4 0 4 0M2 18c3-4 5 4 8 0s5 4 8 0 4 0 4 0"/> @break
@case('gem') <path d="m3 8 4-5h10l4 5-9 14Zm0 0h18M7 3l5 19 5-19M8 8l4-5 4 5"/> @break
@case('crown') <path d="m3 5 4 4 5-6 5 6 4-4-2 13H5Zm3 16h12"/> @break
@case('moon') <path d="M20 14A9 9 0 0 1 10 3a9 9 0 1 0 10 11Z"/> @break
@case('sun') <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/> @break
@case('snowflake') <path d="M12 2v20M3.3 7l17.4 10M3.3 17 20.7 7M9 4l3 3 3-3M9 20l3-3 3 3M3 10l4-1-1-4m15 9-4 1 1 4M6 19l1-4-4-1M18 5l-1 4 4 1"/> @break
@case('briefcase') <rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12c6 4 12 4 18 0M12 12v4"/> @break
@case('glass') <path d="M8 3h8l1 7a5 5 0 0 1-10 0Zm4 12v6m-4 0h8M7 9h10"/> @break
@case('building') <path d="M5 22V3h14v19M9 22v-5h6v5M9 7h1m4 0h1M9 11h1m4 0h1M3 22h18"/> @break
@case('beach') <path d="M3 12a9 9 0 0 1 18 0H3Zm9-9c-3 3-4 6-4 9m4-9c3 3 4 6 4 9M12 12v8M3 22c4-3 6 1 10-1s6 0 8 0"/> @break
@case('candy') <rect x="7" y="7" width="10" height="10" rx="4"/><path d="m7 9-5-3v12l5-3m10-6 5-3v12l-5-3m-7 2 4-10"/> @break
@case('bottle') <path d="M9 2h6v4H9zM8 6h8v3l3 3v9H5v-9l3-3Zm-3 9h14M9 18h6"/> @break
@case('vial') <path d="M8 2h8m-7 0v12l-5 6a1 1 0 0 0 1 2h14a1 1 0 0 0 1-2l-5-6V2M7 17h10"/> @break
@case('drop') <path d="M12 2S5 10 5 15a7 7 0 0 0 14 0c0-5-7-13-7-13Z"/> @break
@case('pen') <path d="m15 3 6 6-11 11-7 1 1-7Zm-9 13 2 2M13 5l6 6"/> @break
@case('eye') <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/> @break
@case('trash') <path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/> @break
@case('scale') <path d="M12 3v18m-5 0h10M3 7h18M6 7l-4 8h8Zm12 0-4 8h8Z"/> @break
@case('wheel') <circle cx="12" cy="11" r="8"/><circle cx="12" cy="11" r="2"/><path d="M12 3v6m0 4v6M4 11h6m4 0h6M6.3 5.3l4.3 4.3m2.8 2.8 4.3 4.3M6.3 16.7l4.3-4.3m2.8-2.8 4.3-4.3M9 19l-2 3m8-3 2 3"/> @break
@case('party') <path d="m9 8 7 7-13 6Zm7-12v3m2 2h3M13 7l2-3m4 7 3-1M5 15l4 4"/><circle cx="20" cy="4" r="1"/> @break
@case('ribbon') <circle cx="12" cy="8" r="5"/><path d="m8 12-3 9 7-3 7 3-3-9"/> @break
@case('trophy') <path d="M7 3h10v6a5 5 0 0 1-10 0Zm0 2H3v3a4 4 0 0 0 4 4m10-7h4v3a4 4 0 0 1-4 4M12 14v7m-4 0h8"/> @break
@case('video') <rect x="3" y="5" width="12" height="14" rx="2"/><path d="m15 9 6-4v14l-6-4"/> @break
@case('play') <path d="m8 4 13 8-13 8Z"/> @break
@case('broadcast') <circle cx="12" cy="12" r="2"/><path d="M7 7a7 7 0 0 0 0 10m10-10a7 7 0 0 1 0 10M4 4a11 11 0 0 0 0 16M20 4a11 11 0 0 1 0 16"/> @break
@case('warning') <path d="m12 3 10 18H2Zm0 6v5m0 3v1"/> @break
@case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-11v1"/> @break
@case('phone') <rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 18h4"/> @break
@case('pin') <path d="M19 9c0 6-7 13-7 13S5 15 5 9a7 7 0 0 1 14 0Z"/><circle cx="12" cy="9" r="2"/> @break
@case('tag') <path d="M3 3h8l10 10-8 8L3 11Z"/><circle cx="7" cy="7" r="1"/> @break
@case('bulb') <path d="M8 16a7 7 0 1 1 8 0v3H8Zm1 6h6M10 10l2 3 2-3m-2 3v6"/> @break
@case('refresh') <path d="M20 7V2m0 5h-5M4 17v5m0-5h5M20 7A9 9 0 0 0 3 10m1 7a9 9 0 0 0 17-3"/> @break
@case('card') <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20M6 15h4"/> @break
@case('bank') <path d="m2 8 10-6 10 6H2Zm2 13h16M6 8v10m6-10v10m6-10v10M2 22h20"/> @break
@case('cash') <rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/> @break
@case('copy') <rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V3H3v13h5"/> @break
@case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 11h18m-14 4h2m3 0h2"/> @break
@case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/> @break
@case('hourglass') <path d="M5 3h14M5 21h14M7 3v4l5 5-5 5v4m10-18v4l-5 5 5 5v4M9 18h6"/> @break
@case('logout') <path d="M9 3H3v18h6m5-15 6 6-6 6M8 12h12"/> @break
@case('link') <path d="m10 7 3-3a5 5 0 0 1 7 7l-3 3M7 10l-3 3a5 5 0 0 0 7 7l3-3M8 16l8-8"/> @break
@case('home') <path d="m2 11 10-9 10 9M5 9v13h5v-7h4v7h5V9"/> @break
@case('globe') <circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/> @break
@case('mirror') <ellipse cx="12" cy="9" rx="7" ry="8"/><path d="M12 17v5m-4 0h8M9 9l4-4"/> @break
@default <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/>
@endswitch
</svg>
