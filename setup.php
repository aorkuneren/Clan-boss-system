<?php
/**
 * Klan Yönetim Sistemi - Database Setup
 * Veritabanı bağlantısı ve yapılandırma dosyası
 */

class DatabaseSetup {
    
    // Veritabanı bağlantı ayarları
    private const DB_CONFIG = [
        'host' => '127.0.0.1',
        'dbname' => 'clanbossv3',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
    
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Veritabanına bağlantı kurma
     */
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                self::DB_CONFIG['host'],
                self::DB_CONFIG['dbname'],
                self::DB_CONFIG['charset']
            );
            
            $this->pdo = new PDO(
                $dsn,
                self::DB_CONFIG['username'],
                self::DB_CONFIG['password'],
                self::DB_CONFIG['options']
            );
            
            echo "✅ Veritabanı bağlantısı başarılı!\n";
            
        } catch (PDOException $e) {
            die("❌ Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    /**
     * PDO instance'ını döndür
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Veritabanı tablolarının durumunu kontrol et
     */
    public function checkTables() {
        $tables = [
            'users', 'events', 'event_participants', 
            'drops', 'payouts', 'clan_bank', 
            'payments', 'settings'
        ];
        
        echo "\n📋 Tablo kontrolleri:\n";
        echo str_repeat("-", 40) . "\n";
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM `$table`");
                $count = $stmt->fetchColumn();
                echo sprintf("✅ %-20s: %d kayıt\n", $table, $count);
            } catch (PDOException $e) {
                echo sprintf("❌ %-20s: Tablo bulunamadı\n", $table);
            }
        }
    }
    
    /**
     * Sistem ayarlarını kontrol et
     */
    public function checkSettings() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM settings WHERE id = 1");
            $settings = $stmt->fetch();
            
            if ($settings) {
                echo "\n⚙️  Sistem Ayarları:\n";
                echo str_repeat("-", 40) . "\n";
                echo "Vergi Durumu: " . ($settings['tax_active'] ? 'Aktif' : 'Pasif') . "\n";
                echo "Vergi Oranı: %" . $settings['tax_percent'] . "\n";
                echo "Vergi Başlangıç: " . $settings['tax_start_date'] . "\n";
                echo "Son Güncelleme: " . $settings['updated_at'] . "\n";
            }
        } catch (PDOException $e) {
            echo "❌ Ayarlar kontrol edilemedi: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Kullanıcı istatistiklerini göster
     */
    public function showUserStats() {
        try {
            // Rol bazında kullanıcı sayıları
            $stmt = $this->pdo->query("
                SELECT role, COUNT(*) as count 
                FROM users 
                WHERE is_active = 1 
                GROUP BY role
            ");
            $roles = $stmt->fetchAll();
            
            echo "\n👥 Aktif Kullanıcı İstatistikleri:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($roles as $role) {
                echo sprintf("%-10s: %d kişi\n", $role['role'], $role['count']);
            }
            
            // Sınıf bazında kullanıcı sayıları
            $stmt = $this->pdo->query("
                SELECT job, COUNT(*) as count 
                FROM users 
                WHERE is_active = 1 
                GROUP BY job
            ");
            $jobs = $stmt->fetchAll();
            
            echo "\n⚔️  Sınıf Dağılımı:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($jobs as $job) {
                echo sprintf("%-10s: %d kişi\n", $job['job'], $job['count']);
            }
            
        } catch (PDOException $e) {
            echo "❌ Kullanıcı istatistikleri alınamadı: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Klan bankası durumunu göster
     */
    public function showBankStatus() {
        try {
            $stmt = $this->pdo->query("
                SELECT SUM(tax_amount) as total_tax
                FROM clan_bank
            ");
            $result = $stmt->fetch();
            
            echo "\n🏦 Klan Bankası:\n";
            echo str_repeat("-", 40) . "\n";
            echo "Toplam Vergi: " . number_format($result['total_tax'] ?? 0) . " altın\n";
            
            // Son 5 vergi kaydı
            $stmt = $this->pdo->query("
                SELECT cb.tax_amount, u.username, cb.created_at
                FROM clan_bank cb
                JOIN users u ON cb.user_id = u.id
                ORDER BY cb.created_at DESC
                LIMIT 5
            ");
            $recent = $stmt->fetchAll();
            
            if ($recent) {
                echo "\n📊 Son Vergi Kayıtları:\n";
                foreach ($recent as $record) {
                    echo sprintf("• %s: %s altın (%s)\n", 
                        $record['username'], 
                        number_format($record['tax_amount']),
                        date('d.m.Y H:i', strtotime($record['created_at']))
                    );
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ Banka durumu alınamadı: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Admin sayısını kontrol et ve güvenlik uyarısı ver
     */
    public function checkAdminSecurity() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'Admin' AND is_active = 1");
            $stmt->execute();
            $result = $stmt->fetch();
            
            echo "\n🔐 Admin Güvenlik Durumu:\n";
            echo str_repeat("-", 40) . "\n";
            echo "Aktif Admin Sayısı: " . $result['admin_count'] . "\n";
            
            if ($result['admin_count'] == 0) {
                echo "🚨 UYARI: Hiç aktif admin yok! Sistem erişilemez durumda!\n";
                echo "Acil admin oluşturmanız gerekiyor.\n";
            } elseif ($result['admin_count'] == 1) {
                echo "⚠️  DİKKAT: Sadece 1 admin var! Yedek admin oluşturmanız önerilir.\n";
            } else {
                echo "✅ Admin güvenliği uygun.\n";
            }
            
            // Admin listesi
            $stmt = $this->pdo->prepare("SELECT username, job FROM users WHERE role = 'Admin' AND is_active = 1");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            if ($admins) {
                echo "\n👑 Aktif Adminler:\n";
                foreach ($admins as $admin) {
                    echo "• {$admin['username']} ({$admin['job']})\n";
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ Admin kontrolü yapılamadı: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Admin kullanıcısı oluştur
     */
    public function createAdmin($username = 'admin', $password = 'admin123', $job = 'Rogue') {
        try {
            // Mevcut admin sayısını kontrol et
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'Admin' AND is_active = 1");
            $stmt->execute();
            $result = $stmt->fetch();
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, job, role, is_active) 
                VALUES (?, ?, ?, 'Admin', 1)
            ");
            
            $stmt->execute([$username, $hashedPassword, $job]);
            
            $newAdminCount = $result['admin_count'] + 1;
            
            echo "\n✅ Admin kullanıcısı oluşturuldu:\n";
            echo "Kullanıcı Adı: $username\n";
            echo "Şifre: $password\n";
            echo "Sınıf: $job\n";
            echo "Rol: Admin\n";
            echo "Toplam Admin Sayısı: $newAdminCount\n";
            
            return true;
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "⚠️  '$username' kullanıcı adı zaten kullanımda!\n";
            } else {
                echo "❌ Admin kullanıcısı oluşturulamadı: " . $e->getMessage() . "\n";
            }
            return false;
        }
    }

    /**
     * Test kullanıcısı oluştur
     */
    public function createTestUser($username = 'test_user', $password = '123456') {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, job, role, is_active) 
                VALUES (?, ?, 'Warrior', 'Üye', 1)
            ");
            
            $stmt->execute([$username, $hashedPassword]);
            
            echo "\n✅ Test kullanıcısı oluşturuldu:\n";
            echo "Kullanıcı Adı: $username\n";
            echo "Şifre: $password\n";
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "⚠️  '$username' kullanıcısı zaten mevcut!\n";
            } else {
                echo "❌ Test kullanıcısı oluşturulamadı: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Veritabanı backup oluştur
     */
    public function createBackup($filename = null) {
        if (!$filename) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $tables = [
            'users', 'events', 'event_participants', 
            'drops', 'payouts', 'clan_bank', 
            'payments', 'settings'
        ];
        
        $backup = "-- Klan Yönetim Sistemi Backup\n";
        $backup .= "-- Oluşturulma Tarihi: " . date('Y-m-d H:i:s') . "\n\n";
        $backup .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $backup .= "START TRANSACTION;\n";
        $backup .= "SET time_zone = \"+00:00\";\n\n";
        
        try {
            foreach ($tables as $table) {
                $stmt = $this->pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll();
                
                if ($rows) {
                    $backup .= "-- Tablo: $table\n";
                    $backup .= "TRUNCATE TABLE `$table`;\n";
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                        }, array_values($row));
                        
                        $backup .= sprintf("INSERT INTO `%s` VALUES (%s);\n", 
                            $table, implode(', ', $values));
                    }
                    $backup .= "\n";
                }
            }
            
            $backup .= "COMMIT;\n";
            
            file_put_contents($filename, $backup);
            echo "✅ Backup oluşturuldu: $filename\n";
            
        } catch (Exception $e) {
            echo "❌ Backup oluşturulamadı: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Sistem sağlık kontrolü
     */
    public function healthCheck() {
        echo "\n🏥 Sistem Sağlık Kontrolü:\n";
        echo str_repeat("=", 50) . "\n";
        
        // Bağlantı kontrolü
        echo "Veritabanı Bağlantısı: ";
        echo $this->pdo ? "✅ OK\n" : "❌ HATA\n";
        
        // Tablo kontrolleri
        $this->checkTables();
        
        // Sistem ayarları
        $this->checkSettings();
        
        // Kullanıcı istatistikleri
        $this->showUserStats();
        
        // Klan bankası
        $this->showBankStatus();
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Sistem kontrolü tamamlandı!\n";
    }
}

// Kullanım örneği
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    echo "🚀 Klan Yönetim Sistemi - Setup Başlatılıyor...\n";
    echo str_repeat("=", 50) . "\n";
    
    try {
        $setup = new DatabaseSetup();
        
        // Admin güvenlik kontrolü
        $setup->checkAdminSecurity();

        // Sistem sağlık kontrolü
        $setup->healthCheck();
        
        // Menü
        echo "\n📋 Kullanılabilir Komutlar:\n";
        echo "1. Admin kullanıcısı oluştur\n";
        echo "2. Test kullanıcısı oluştur\n";
        echo "3. Backup oluştur\n";
        echo "4. Çıkış\n";
        echo "\nSeçiminiz (1-4): ";
        
        $choice = trim(fgets(STDIN));
        
        switch ($choice) {
            case '1':
                echo "Admin kullanıcı adı (varsayılan: admin): ";
                $username = trim(fgets(STDIN)) ?: 'admin';
                echo "Şifre (varsayılan: admin123): ";
                $password = trim(fgets(STDIN)) ?: 'admin123';
                echo "Sınıf (Warrior/Rogue/Mage/Priest, varsayılan: Rogue): ";
                $job = trim(fgets(STDIN)) ?: 'Rogue';
                
                $validJobs = ['Warrior', 'Rogue', 'Mage', 'Priest'];
                if (!in_array($job, $validJobs)) {
                    echo "⚠️  Geçersiz sınıf! Rogue olarak ayarlandı.\n";
                    $job = 'Rogue';
                }
                
                $setup->createAdmin($username, $password, $job);
                break;
                
            case '2':
                echo "Test kullanıcı adı (varsayılan: test_user): ";
                $username = trim(fgets(STDIN)) ?: 'test_user';
                echo "Şifre (varsayılan: 123456): ";
                $password = trim(fgets(STDIN)) ?: '123456';
                $setup->createTestUser($username, $password);
                break;
                
            case '3':
                $setup->createBackup();
                break;
                
            case '4':
                echo "👋 Çıkış yapılıyor...\n";
                break;
                
            default:
                echo "⚠️  Geçersiz seçim!\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Kritik hata: " . $e->getMessage() . "\n";
    }
}

/**
 * Global veritabanı bağlantısı fonksiyonu
 */
function getDatabase() {
    static $instance = null;
    if ($instance === null) {
        $instance = new DatabaseSetup();
    }
    return $instance->getPDO();
}

/**
 * Güvenli SQL sorgusu çalıştırma
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Hatası: " . $e->getMessage());
        throw new Exception("Veritabanı işlemi başarısız!");
    }
}

/**
 * Kullanıcı doğrulama
 */
function authenticateUser($username, $password) {
    $stmt = executeQuery(
        "SELECT id, username, password, role, job, is_active FROM users WHERE username = ? AND is_active = 1",
        [$username]
    );
    
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        return $user;
    }
    
    return false;
}

/**
 * Log kaydetme
 */
function logActivity($user_id, $action, $details = null) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ];
    
    error_log("CLAN_LOG: " . json_encode($log_entry, JSON_UNESCAPED_UNICODE));
}
?>
