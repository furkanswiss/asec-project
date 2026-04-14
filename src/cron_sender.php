<?php
// Hataları görmek için
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Email Notification System - Cron Sender
 */

// ============================================================================
// 0. ENVIRONMENT VARIABLES LOADER (ÖNCE FONKSİYON TANIMLANIR)
// ============================================================================
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) { return; }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) { continue; }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\' '); 
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// ============================================================================
// 0.1. DOSYA YOLU TANIMI (BURASI ÇOK ÖNEMLİ - EN ÜSTTE OLMALI)
// ============================================================================
// Dosya cron_sender.php ile AYNI klasörde (src içinde)
$envPath = __DIR__ . '/.env'; 


// ============================================================================
// DEBUG MODU (Sorun çözülünce burayı silebilirsiniz)
// ============================================================================
echo "<div style='background:#fff3cd; padding:15px; border:1px solid #ffeeba; font-family:sans-serif;'>";
echo "<h3>🔍 Debug Analizi</h3>";
echo "<strong>📂 Aranan Dosya Yolu:</strong> " . $envPath . "<br>";

if (file_exists($envPath)) {
    echo "✅ <strong>DURUM:</strong> Dosya bulundu!<br>";
    // Dosyayı yükle
    loadEnvFile($envPath);
    
    // Değişkenleri kontrol et
    $test_cron = $_ENV['CRON_KEY'] ?? getenv('CRON_KEY');
    $test_smtp = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD');
    
    echo "<strong>🔑 CRON_KEY:</strong> " . ($test_cron ? "OK (Mevcut)" : "❌ YOK") . "<br>";
    echo "<strong>🔑 SMTP_PASSWORD:</strong> " . ($test_smtp ? "OK (Mevcut)" : "❌ YOK") . "<br>";
    
} else {
    echo "❌ <strong>HATA:</strong> Dosya bulunamadı!<br>";
    echo "Lütfen bilgisayarınızdaki <code>src</code> klasörünün içine <code>.env</code> adında bir dosya oluşturduğunuzdan emin olun.<br>";
    echo "Şu anki klasör (__DIR__): " . __DIR__;
}
echo "</div><hr>";
// ============================================================================


// Eğer dosya yoksa aşağıya devam etme, hata verip dur.
if (!file_exists($envPath)) {
    die("Sistem durduruldu: .env dosyası eksik.");
}

// Dosyayı yükle (Debug kısmında yüklemiş olsak da garanti olsun)
loadEnvFile($envPath);


// ============================================================================
// 1. SECURITY CHECK
// ============================================================================
// Check if running from Command Line Interface (CLI)
$is_cli = (php_sapi_name() === 'cli' || defined('STDIN'));

if (!$is_cli) {
    // We are in a Web Browser -> Enforce Security Key
    $cron_key = $_ENV['CRON_KEY'] ?? getenv('CRON_KEY'); 

    if (empty($cron_key)) {
        die('HATA: CRON_KEY değeri .env dosyasında bulunamadı.');
    }

    if (!isset($_GET['key']) || $_GET['key'] !== $cron_key) {
        http_response_code(403);
        die('Erişim Reddedildi: Yanlış güvenlik anahtarı (Key mismatch).');
    }
}
// If $is_cli is true, we skip the check and proceed (Trusted Execution).

// ============================================================================
// 2. PHPMailer Setup
// ============================================================================
$base_path = __DIR__ . '/PHPMailer';

if (!file_exists($base_path . '/Exception.php')) {
    die("HATA: PHPMailer dosyaları bulunamadı: " . $base_path);
}

require_once $base_path . '/Exception.php';
require_once $base_path . '/PHPMailer.php';
require_once $base_path . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================================
// 3. CONFIGURATION
// ============================================================================
$db_host = getenv('DB_HOST') ?: 'database';
$db_name = getenv('DB_NAME') ?: 'db_asec';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: 'root';

$smtp_host = 'smtp-relay.brevo.com';
$smtp_port = 587;
$smtp_encryption = 'tls'; 
$smtp_username = '9e08aa001@smtp-brevo.com'; 
$smtp_from_email = 'web@aybuasec.org'; 
$smtp_from_name = 'ASEC Kulübü';
$batch_size = 20; 

// ============================================================================
// 4. DATABASE CONNECTION
// ============================================================================
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // İŞTE BEKÇİYE VERİLEN TÜRKÇE EMRİ BURADA:
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// ============================================================================
// STEP 1: AUTO-UPDATE DATABASE SCHEMA (Self-Healing)
// ============================================================================
try {
    // First check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'mail_queue'");
    if ($stmt->rowCount() > 0) {
        // Table exists, check for missing columns
        // Check if priority column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM mail_queue LIKE 'priority'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE mail_queue ADD COLUMN priority INT DEFAULT 1 COMMENT '1=Normal, 10=High'");
            echo "✅ priority sütunu eklendi.<br>";
        }
        
        // Check if error_message column exists (note: table creation uses error_msg, but we'll check for both)
        $stmt = $pdo->query("SHOW COLUMNS FROM mail_queue LIKE 'error_message'");
        if ($stmt->rowCount() === 0) {
            // Check if error_msg exists (the original column name)
            $stmt = $pdo->query("SHOW COLUMNS FROM mail_queue LIKE 'error_msg'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("ALTER TABLE mail_queue ADD COLUMN error_message TEXT DEFAULT NULL");
                echo "✅ error_message sütunu eklendi.<br>";
            }
        }
    }
    // If table doesn't exist, it will be created later with all columns
} catch (PDOException $e) {
    // Continue execution even if schema check fails
    // Table will be created later with all required columns
}

// ============================================================================
// STEP 2: PREVENT RACE CONDITIONS (File Locking - Self-Healing)
// ============================================================================
$lock_file = __DIR__ . '/cron_sender.lock';
$lock_timeout = 300; // 5 minutes in seconds

// Self-Healing Lock: Check if lock file exists and handle stale locks
if (file_exists($lock_file)) {
    $lock_age = time() - filemtime($lock_file);
    
    if ($lock_age < $lock_timeout) {
        // Another instance is running (lock is fresh)
        die("⚠️ Başka bir cron_sender.php örneği çalışıyor. Bu işlem durduruldu. (Lock file: {$lock_age} saniye önce oluşturuldu)");
    } else {
        // Lock file is stale (older than 5 minutes) - Self-healing: DELETE it
        if (@unlink($lock_file)) {
            echo "🔓 Eski kilit dosyası silindi (Stale lock removed: {$lock_age} saniye eski).<br>";
        } else {
            echo "⚠️ Uyarı: Eski kilit dosyası silinemedi, devam ediliyor...<br>";
        }
    }
}

// Create lock file
if (@touch($lock_file)) {
    echo "🔒 Kilit dosyası oluşturuldu.<br>";
} else {
    echo "⚠️ Uyarı: Kilit dosyası oluşturulamadı, devam ediliyor...<br>";
}

// Register shutdown function to ensure lock file is deleted even if script crashes
register_shutdown_function(function() use ($lock_file) {
    if (file_exists($lock_file)) {
        @unlink($lock_file);
    }
});

// ============================================================================
// 4.5. LOAD SMTP CREDENTIALS
// ============================================================================
$smtp_password = $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD');

if (empty($smtp_password)) {
    // Clean up lock file before exiting
    if (file_exists($lock_file)) {
        @unlink($lock_file);
    }
    die('HATA: SMTP_PASSWORD değeri .env dosyasında bulunamadı.');
}

// ============================================================================
// 5. & 6. MAIL FUNCTION
// ============================================================================
// Tablo kontrol
$pdo->exec("CREATE TABLE IF NOT EXISTS mail_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) DEFAULT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    status TINYINT DEFAULT 0,
    priority INT DEFAULT 1 COMMENT '1=Normal, 10=High',
    error_msg TEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function sendEmail($queueItem, $pdo) {
    global $smtp_host, $smtp_port, $smtp_encryption, $smtp_username, $smtp_password, $smtp_from_email, $smtp_from_name;
    
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0; // Hata yoksa 0 yapın
        $mail->Debugoutput = 'html'; 

        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($queueItem['recipient_email'], $queueItem['recipient_name'] ?? '');
        
        $mail->isHTML(true);
        $mail->Subject = $queueItem['subject'];
        $mail->Body = $queueItem['body'];
        $mail->AltBody = strip_tags($queueItem['body']);
        
        $mail->send();
        
        $stmt = $pdo->prepare("UPDATE mail_queue SET status = 1, sent_at = NOW() WHERE id = ?");
        $stmt->execute([$queueItem['id']]);
        return true;
    } catch (Exception $e) {
        $error_msg = $mail->ErrorInfo;
        // Try error_message first, fallback to error_msg for backward compatibility
        try {
            $stmt = $pdo->prepare("UPDATE mail_queue SET status = 2, error_message = ? WHERE id = ?");
            $stmt->execute([$error_msg, $queueItem['id']]);
        } catch (PDOException $e) {
            // Fallback to error_msg if error_message column doesn't exist
            $stmt = $pdo->prepare("UPDATE mail_queue SET status = 2, error_msg = ? WHERE id = ?");
            $stmt->execute([$error_msg, $queueItem['id']]);
        }
        echo "<br><strong>Mailer Error:</strong> " . $error_msg . "<br>";
        return false;
    }
}

// ============================================================================
// STEP 3: PRIORITY-BASED SENDING LOGIC
// ============================================================================
// Main process with priority ordering: High priority (10) first, then by creation time
$stmt = $pdo->prepare("SELECT * FROM mail_queue WHERE status = 0 ORDER BY priority DESC, created_at ASC LIMIT ?");
$stmt->bindValue(1, $batch_size, PDO::PARAM_INT);
$stmt->execute();
$pending_emails = $stmt->fetchAll();

if (empty($pending_emails)) {
    echo "İşlenecek bekleyen e-posta yok (Kuyruk boş).";
    exit;
}

echo "İşlenen e-posta sayısı: " . count($pending_emails) . "<br><hr>";

foreach ($pending_emails as $email) {
    // Debug output: Show email ID being processed
    echo "Processing email ID: " . $email['id'] . " | ";
    
    $priority_label = isset($email['priority']) && $email['priority'] == 10 ? ' [YÜKSEK ÖNCELİK]' : '';
    $priority_info = isset($email['priority']) ? " (Priority: {$email['priority']})" : " (Priority: not set)";
    echo "Gönderiliyor: {$email['recipient_email']}{$priority_label}{$priority_info} ... ";
    if (sendEmail($email, $pdo)) {
        echo "<span style='color:green'>BAŞARILI</span><br>";
    } else {
        echo "<span style='color:red'>BAŞARISIZ</span><br>";
    }
    flush(); 
    sleep(1); 
}
echo "<hr>İşlem tamamlandı.";

// Clean up lock file at the end
if (file_exists($lock_file)) {
    @unlink($lock_file);
}
?>