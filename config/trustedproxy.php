<?php

/*
|--------------------------------------------------------------------------
| Trusted reverse proxies
|--------------------------------------------------------------------------
|
| Hanya IP/proxy di daftar ini yang boleh mengatur header X-Forwarded-*.
| cloudflared di host yang sama + Cloudflare edge; IP klien asli lewat CF tetap
| terbaca, sementara X-Forwarded-For dari koneksi langsung (bukan proxy
| terpercaya) diabaikan — mencegah pemalsuan IP untuk melewati throttle login.
|
| Rentang IPv4/IPv6 Cloudflare di bawah diambil dari sumber resmi:
|   https://www.cloudflare.com/ips-v4
|   https://www.cloudflare.com/ips-v6
| Tanggal pengambilan daftar: 2026-10-01 — perbarui dari URL di atas bila CF
| mengumumkan perubahan rentang IP.
|
*/

return [

    'proxies' => [
        '127.0.0.1',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        // Cloudflare IPv4 (2026-10-01)
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        // Cloudflare IPv6 (2026-10-01)
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ],

];
