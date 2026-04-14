<?php
/**
 * Email Queue Helper Functions
 * 
 * Include this file in your PHP scripts to easily queue emails.
 * 
 * Usage:
 * require_once __DIR__ . '/includes/email_queue_helper.php';
 * 
 * Then use the helper functions to queue emails.
 */

/**
 * Ensure mail_queue table exists
 * Creates the table if it doesn't exist to prevent "Table not found" errors
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function ensureMailQueueTableExists($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS mail_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(255) NOT NULL,
        recipient_name VARCHAR(255) DEFAULT NULL,
        subject VARCHAR(500) NOT NULL,
        body TEXT NOT NULL,
        status TINYINT DEFAULT 0 COMMENT '0=pending, 1=sent, 2=error',
        error_msg TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to create mail_queue table: " . $e->getMessage());
        return false;
    }
}

/**
 * Queue a new listing notification (Scenario A)
 * Sends to individual users only (from users table)
 * 
 * @param PDO $pdo Database connection
 * @param string $listing_title Title of the listing
 * @param string $listing_url URL to view the listing
 * @return int Number of emails queued
 */
function queueNewListingNotification($pdo, $listing_title, $listing_url) {
    // Ensure table exists
    ensureMailQueueTableExists($pdo);
    
    try {
        // Query all individual users from users table (no user_type column exists)
        $stmt = $pdo->query("SELECT id, email, name FROM users");
        $individual_users = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch individual users: " . $e->getMessage());
        return 0;
    }
    
    if (empty($individual_users)) {
        return 0;
    }
    
    $inserted = 0;
    $subject = "Yeni İlan: " . $listing_title;
    
    // Base notification body (NOT full content - just a notification)
    $base_body = "
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #9370db;'>Yeni İlan Yayınlandı</h2>
            <p>Merhaba,</p>
            <p>Yeni bir ilan yayınlandı: <strong>" . htmlspecialchars($listing_title) . "</strong></p>
            <p>Detayları görmek için aşağıdaki bağlantıya tıklayın:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='" . htmlspecialchars($listing_url) . "' 
                   style='background-color: #9370db; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                    İlanı Görüntüle
                </a>
            </p>
            <p style='color: #666; font-size: 0.9em;'>
                Bu e-posta ASEC Kulübü tarafından gönderilmiştir.
            </p>
        </div>
    </body>
    </html>
    ";
    
    // Insert into queue for each individual user
    $insert_stmt = $pdo->prepare("
        INSERT INTO mail_queue (recipient_email, recipient_name, subject, body, status) 
        VALUES (?, ?, ?, ?, 0)
    ");
    
    foreach ($individual_users as $user) {
        $recipient_name = $user['name'] ?? 'Üye';
        $personalized_body = str_replace('Merhaba,', "Merhaba {$recipient_name},", $base_body);
        
        try {
            $insert_stmt->execute([
                $user['email'],
                $recipient_name,
                $subject,
                $personalized_body
            ]);
            $inserted++;
        } catch (PDOException $e) {
            error_log("Failed to queue email for user {$user['email']}: " . $e->getMessage());
        }
    }
    
    return $inserted;
}

/**
 * Queue an announcement/event notification (Scenario B)
 * Sends to both individual AND corporate users
 * 
 * @param PDO $pdo Database connection
 * @param string $announcement_title Title of the announcement/event
 * @param string $announcement_url URL to view the announcement/event
 * @return int Number of emails queued
 */
function queueAnnouncementNotification($pdo, $announcement_title, $announcement_url) {
    // Ensure table exists
    ensureMailQueueTableExists($pdo);
    
    $all_users = [];
    
    try {
        // Query individual users from users table
        $stmt_individual = $pdo->query("SELECT email, name FROM users");
        $individual_users = $stmt_individual->fetchAll();
        $all_users = array_merge($all_users, $individual_users);
    } catch (PDOException $e) {
        error_log("Failed to fetch individual users: " . $e->getMessage());
    }
    
    try {
        // Query corporate users from corporate_users table
        $stmt_corporate = $pdo->query("SELECT email, contact_person as name FROM corporate_users");
        $corporate_users = $stmt_corporate->fetchAll();
        $all_users = array_merge($all_users, $corporate_users);
    } catch (PDOException $e) {
        error_log("Failed to fetch corporate users: " . $e->getMessage());
    }
    
    if (empty($all_users)) {
        return 0;
    }
    
    $inserted = 0;
    $subject = "Yeni Duyuru: " . $announcement_title;
    
    // Base notification body (NOT full content - just a notification)
    $base_body = "
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #9370db;'>Yeni Duyuru</h2>
            <p>Merhaba,</p>
            <p>Yeni bir duyuru yayınlandı: <strong>" . htmlspecialchars($announcement_title) . "</strong></p>
            <p>Detayları görmek için aşağıdaki bağlantıya tıklayın:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='" . htmlspecialchars($announcement_url) . "' 
                   style='background-color: #9370db; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Duyuruyu Görüntüle
                </a>
            </p>
            <p style='color: #666; font-size: 0.9em;'>
                Bu e-posta ASEC Kulübü tarafından gönderilmiştir.
            </p>
        </div>
    </body>
    </html>
    ";
    
    // Insert into queue for each user
    $insert_stmt = $pdo->prepare("
        INSERT INTO mail_queue (recipient_email, recipient_name, subject, body, status) 
        VALUES (?, ?, ?, ?, 0)
    ");
    
    foreach ($all_users as $user) {
        $recipient_name = $user['name'] ?? 'Üye';
        $personalized_body = str_replace('Merhaba,', "Merhaba {$recipient_name},", $base_body);
        
        try {
            $insert_stmt->execute([
                $user['email'],
                $recipient_name,
                $subject,
                $personalized_body
            ]);
            $inserted++;
        } catch (PDOException $e) {
            error_log("Failed to queue email for user {$user['email']}: " . $e->getMessage());
        }
    }
    
    return $inserted;
}

/**
 * Queue an event notification (Scenario C - Events)
 * Sends to both individual AND corporate users
 * * @param PDO $pdo Database connection
 * @param string $event_title Title of the event
 * @param string $event_url URL to view the event
 * @return int Number of emails queued
 */
function queueEventNotification($pdo, $event_title, $event_url) {
    // Ensure table exists (Tablonun var olduğundan emin oluyoruz)
    // Eğer bu fonksiyon sizde global değilse, include etmeniz gerekebilir.
    // Ancak aynı dosyadaysa sorun yok.
    if (function_exists('ensureMailQueueTableExists')) {
        ensureMailQueueTableExists($pdo);
    }
    
    $all_users = [];
    
    // 1. Bireysel Kullanıcıları Çek
    try {
        $stmt_individual = $pdo->query("SELECT email, name FROM users");
        $individual_users = $stmt_individual->fetchAll();
        $all_users = array_merge($all_users, $individual_users);
    } catch (PDOException $e) {
        error_log("Failed to fetch individual users: " . $e->getMessage());
    }
    
    // 2. Kurumsal Kullanıcıları Çek
    try {
        $stmt_corporate = $pdo->query("SELECT email, contact_person as name FROM corporate_users");
        $corporate_users = $stmt_corporate->fetchAll();
        $all_users = array_merge($all_users, $corporate_users);
    } catch (PDOException $e) {
        error_log("Failed to fetch corporate users: " . $e->getMessage());
    }
    
    if (empty($all_users)) {
        return 0;
    }
    
    $inserted = 0;
    
    // --- DEĞİŞİKLİK 1: Konu Başlığı ---
    $subject = "Yeni Etkinlik: " . $event_title;
    
    // Base notification body
    $base_body = "
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            
            <h2 style='color: #2ecc71;'>Yeni Etkinlik</h2>
            
            <p>Merhaba,</p>
            
            <p>Yeni bir etkinlik yayınlandı: <strong>" . htmlspecialchars($event_title) . "</strong></p>
            <p>Katılım detaylarını ve etkinliği incelemek için aşağıdaki bağlantıya tıklayın:</p>
            
            <p style='text-align: center; margin: 30px 0;'>
                <a href='" . htmlspecialchars($event_url) . "' 
                   style='background-color: #2ecc71; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Etkinliği Görüntüle
                </a>
            </p>
            
            <p style='color: #666; font-size: 0.9em;'>
                Bu e-posta ASEC Kulübü tarafından gönderilmiştir.
            </p>
        </div>
    </body>
    </html>
    ";
    
    // Insert into queue
    $insert_stmt = $pdo->prepare("
        INSERT INTO mail_queue (recipient_email, recipient_name, subject, body, status) 
        VALUES (?, ?, ?, ?, 0)
    ");
    
    foreach ($all_users as $user) {
        $recipient_name = $user['name'] ?? 'Üye';
        // Kişiselleştirme kısmı aynı kalır
        $personalized_body = str_replace('Merhaba,', "Merhaba {$recipient_name},", $base_body);
        
        try {
            $insert_stmt->execute([
                $user['email'],
                $recipient_name,
                $subject,
                $personalized_body
            ]);
            $inserted++;
        } catch (PDOException $e) {
            error_log("Failed to queue event email for user {$user['email']}: " . $e->getMessage());
        }
    }
    
    return $inserted;
}

/**
 * Queue a direct message notification (Scenario C)
 * Sends to a specific recipient
 * 
 * @param PDO $pdo Database connection
 * @param string $recipient_email Recipient email address
 * @param string $recipient_name Recipient name
 * @param string $sender_name Sender name
 * @param string $message_url URL to view the message
 * @return bool Success status
 */
function queueDirectMessageNotification($pdo, $recipient_email, $recipient_name, $sender_name, $message_url) {
    // Ensure table exists
    ensureMailQueueTableExists($pdo);
    
    $subject = "Yeni Mesaj: {$sender_name}";
    
    // Notification body (NOT full message content - just a notification)
    $body = "
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #9370db;'>Yeni Mesajınız Var</h2>
            <p>Merhaba {$recipient_name},</p>
            <p><strong>{$sender_name}</strong> size yeni bir mesaj gönderdi.</p>
            <p>Mesajı okumak için aşağıdaki bağlantıya tıklayın:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='" . htmlspecialchars($message_url) . "' 
                   style='background-color: #9370db; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                    Mesajı Görüntüle
                </a>
            </p>
            <p style='color: #666; font-size: 0.9em;'>
                Bu e-posta ASEC Kulübü tarafından gönderilmiştir.
            </p>
        </div>
    </body>
    </html>
    ";
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO mail_queue (recipient_email, recipient_name, subject, body, status) 
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([
            $recipient_email,
            $recipient_name,
            $subject,
            $body
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to queue direct message email: " . $e->getMessage());
        return false;
    }
}

/**
 * Generic function to queue any email
 * 
 * @param PDO $pdo Database connection
 * @param string $recipient_email Recipient email address
 * @param string $recipient_name Recipient name
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param int $priority Email priority (1=Normal, 10=High, default: 1)
 * @return int|false Queue ID on success, false on failure
 */
function queueEmail($pdo, $recipient_email, $recipient_name, $subject, $body, $priority = 1) {
    // Ensure table exists
    ensureMailQueueTableExists($pdo);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO mail_queue (recipient_email, recipient_name, subject, body, status, priority) 
            VALUES (?, ?, ?, ?, 0, ?)
        ");
        $stmt->execute([
            $recipient_email,
            $recipient_name,
            $subject,
            $body,
            $priority
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Failed to queue email: " . $e->getMessage());
        return false;
    }
}

?>
