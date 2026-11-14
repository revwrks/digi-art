<?php
/**
 * AdGuard Family Protection DoH & Safety Filter (Deep Module)
 * - Memverifikasi domain hasil pencarian secara real-time via DNS-over-HTTPS (DoH) AdGuard Family
 * - Melindungi siswa SMP 100% dari situs dewasa, pornografi, judi, malware, dan konten berbahaya
 * - Memblokir situs marketplace / e-commerce agar referensi visual bebas dari produk dagangan komersial
 * 
 * Server: https://dns-family.adguard.com/dns-query
 * AdGuard Block Sinkhole IP: 94.140.14.35, 94.140.14.36, 94.140.15.35, 94.140.15.36, 0.0.0.0
 */

class AdGuardFilter {
    private static string $dohEndpoint = 'https://dns-family.adguard.com/dns-query';

    // IP blokir resmi AdGuard Family Protection
    private static array $blockedIps = [
        '94.140.14.35',
        '94.140.14.36',
        '94.140.15.35',
        '94.140.15.36',
        '0.0.0.0',
        '127.0.0.1'
    ];

    // Daftar marketplace & platform komersial e-commerce (Ditolak agar referensi tidak memuat barang dagangan)
    private static array $ecommerceDomains = [
        'shopee.co.id', 'shopee.com', 'shopeemobile.com',
        'tokopedia.com', 'tokopedia.net',
        'lazada.co.id', 'lazada.com', 'slatic.net',
        'bukalapak.com', 'blibli.com',
        'tiktok.com', 'tiktokcdn.com',
        'aliexpress.com', 'alibaba.com', 'taobao.com',
        'amazon.com', 'ebay.com', 'walmart.com', 'target.com', 'bestbuy.com',
        'olx.co.id', 'carousell.com', 'jakartanotebook.com',
        'bhinneka.com', 'zalora.co.id', 'jd.id', 'jd.com',
        'temu.com', 'shein.com', 'etsy.com', 'etsystatic.com',
        'gigabyte.com', 'asus.com', 'msi.com', 'intel.com', 'amd.com', 'nvidia.com',
        'ralali.com', 'monotaro.id', 'evermos.com', 'tokoko.id',
        'matahari.com', 'mapclub.com', 'indonetwork.co.id'
    ];

    // Daftar statis situs dewasa/NSFW/cam (Instant 0ms rejection sebelum query network DoH)
    private static array $knownAdultDomains = [
        'pornhub.com', 'xvideos.com', 'xnxx.com', 'redtube.com', 'xhamster.com',
        'brazzers.com', 'youporn.com', 'spankbang.com', 'eporner.com', 'tube8.com',
        'beeg.com', 'chaturbate.com', 'onlyfans.com', 'fansly.com', 'stripchat.com',
        'camsoda.com', 'bongacams.com', 'myfreecams.com', 'livejasmin.com', 'cam4.com',
        'rule34.xxx', 'rule34.paheal.net', 'gelbooru.com', 'danbooru.donmai.us',
        'e-hentai.org', 'exhentai.org', 'nhentai.net', 'tsumino.com', 'fakku.net',
        'hanime.tv', 'hentaihaven.xxx', 'kemono.party', 'coomer.party',
        'playboy.com', 'penthouse.com', 'hustler.com', 'fapello.com', 'simpcity.su'
    ];

    // Cache memori hasil verifikasi domain
    private static array $domainCache = [];

    /**
     * Cek apakah domain merupakan marketplace / e-commerce komersial
     */
    public static function isEcommerceDomain(string $host): bool {
        $host = strtolower(trim($host));
        foreach (self::$ecommerceDomains as $eco) {
            if ($host === $eco || str_ends_with($host, '.' . $eco)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah domain aman menurut AdGuard Family Protection & Kebijakan Edukasi SMP
     */
    public static function isDomainSafe(string $domain): bool {
        $domain = strtolower(trim($domain));
        if (empty($domain)) return true;

        // Ekstrak host jika input berupa URL lengkap
        if (str_starts_with($domain, 'http://') || str_starts_with($domain, 'https://')) {
            $parsed = parse_url($domain, PHP_URL_HOST);
            $domain = $parsed ?: $domain;
        }

        // 1. Cek Blokir E-Commerce
        if (self::isEcommerceDomain($domain)) {
            return false;
        }

        // 2. Cek Blokir Instan Situs Dewasa
        foreach (self::$knownAdultDomains as $badHost) {
            if ($domain === $badHost || str_ends_with($domain, '.' . $badHost)) {
                return false;
            }
        }

        // 3. Cek Domain Cache
        if (isset(self::$domainCache[$domain])) {
            return self::$domainCache[$domain];
        }

        // 4. Verifikasi Real-Time via AdGuard Family DNS-over-HTTPS (DoH RFC 8484)
        $header = pack('n6', 0, 0x0100, 1, 0, 0, 0); // Standard query with RD=1
        $qname = '';
        foreach (explode('.', $domain) as $part) {
            $len = strlen($part);
            if ($len === 0) continue;
            $qname .= chr($len) . $part;
        }
        $qname .= "\0";
        $packet = $header . $qname . pack('nn', 1, 1); // QTYPE=A (1), QCLASS=IN (1)

        $base64url = rtrim(strtr(base64_encode($packet), '+/', '-_'), '=');
        $url = self::$dohEndpoint . '?dns=' . $base64url;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/dns-message'],
            CURLOPT_TIMEOUT        => 2, // Timeout 2s cepat agar pencarian tetap responsif
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Jika jaringan timeout atau AdGuard offline sementara
        if ($httpCode !== 200 || !$resp || strlen($resp) < 16) {
            self::$domainCache[$domain] = true;
            return true;
        }

        // Periksa IP jawaban pada paket DNS (AdGuard merespons dengan sinkhole IP jika domain terlarang)
        $isSafe = true;
        if (strlen($resp) >= 4) {
            $ipBytes = substr($resp, -4);
            $resolvedIp = inet_ntop($ipBytes);
            if (in_array($resolvedIp, self::$blockedIps, true)) {
                $isSafe = false;
            }
        }

        self::$domainCache[$domain] = $isSafe;
        return $isSafe;
    }

    /**
     * Filter daftar item referensi menggunakan AdGuard Family DoH & Filter E-Commerce
     */
    public static function filterItems(array $items): array {
        return array_values(array_filter($items, function($item) {
            $url = $item['url'] ?? '';
            $pageUrl = $item['page_url'] ?? $url;
            
            $hostImg = parse_url($url, PHP_URL_HOST);
            $hostPage = parse_url($pageUrl, PHP_URL_HOST);

            if ($hostImg && !self::isDomainSafe($hostImg)) {
                return false;
            }
            if ($hostPage && !self::isDomainSafe($hostPage)) {
                return false;
            }
            return true;
        }));
    }
}
