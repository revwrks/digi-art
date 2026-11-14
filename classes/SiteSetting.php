<?php
/**
 * SiteSetting Model (OOP Deep Module)
 * Mengelola pengaturan dan hiasan beranda (Index) secara fleksibel oleh Administrator.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Activity.php';

class SiteSetting {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil semua pengaturan situs / hiasan beranda
     */
    public function getAll(): array {
        try {
            $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
            }
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Ambil nilai 1 setting
     */
    public function get(string $key, string $default = ''): string {
        try {
            $row = $this->db->fetch("SELECT setting_value FROM site_settings WHERE setting_key = :k", [':k' => $key]);
            return $row['setting_value'] ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Simpan pembaruan dekorasi dan hiasan beranda (Khusus Administrator)
     */
    public function updateMany(array $data): array {
        if (!Auth::hasRole('admin')) {
            return ['success' => false, 'message' => 'Hanya Administrator yang memiliki akses mengubah dekorasi beranda.'];
        }

        $allowedKeys = [
            'home_kicker',
            'home_title',
            'home_highlight',
            'home_description',
            'home_badge',
            'home_theme',
            'home_art_mode',
            'home_custom_art',
            'demo_mode',
            'materi_bab',
            'materi_title',
            'materi_description'
        ];

        foreach ($data as $k => $v) {
            if (in_array($k, $allowedKeys)) {
                $val = trim((string)$v);
                $this->db->query(
                    "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
                    [$k, $val]
                );
            }
        }

        Activity::log("Memperbarui pengaturan hiasan & tampilan Beranda (Index)");

        return [
            'success'  => true,
            'message'  => 'Hiasan dan tampilan beranda berhasil diperbarui!',
            'settings' => $this->getAll()
        ];
    }

    private static ?bool $demoModeCache = null;

    /**
     * Cek status apakah Mode Demo sedang aktif
     */
    public function isDemoMode(): bool {
        return $this->get('demo_mode', '1') === '1';
    }

    /**
     * Helper statis untuk mengecek status Mode Demo
     */
    public static function isDemoModeEnabled(bool $forceRefresh = false): bool {
        if (self::$demoModeCache !== null && !$forceRefresh) {
            return self::$demoModeCache;
        }
        $instance = new self();
        self::$demoModeCache = $instance->isDemoMode();
        return self::$demoModeCache;
    }

    /**
     * Aktifkan atau Nonaktifkan Mode Demo (Khusus Administrator)
     */
    public function setDemoMode(bool $enabled): bool {
        if (!Auth::hasRole('admin')) {
            return false;
        }

        $val = $enabled ? '1' : '0';
        try {
            $this->db->query(
                "INSERT INTO site_settings (setting_key, setting_value) VALUES ('demo_mode', ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
                [$val]
            );

            self::$demoModeCache = $enabled;

            $statusText = $enabled ? 'mengaktifkan akses akun bawaan' : 'menonaktifkan akses akun bawaan (khusus akun resmi)';
            Activity::log("Administrator {$statusText}.");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
