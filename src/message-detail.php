<?php
// Enable error reporting at the very top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any errors
ob_start();

// Include required files
require_once 'db.php';
require_once 'includes/messages.php';
require_once 'includes/lang.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    ob_end_clean();
    header('Location: login.php');
    exit;
}

// Validate and get user information
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$user_type = isset($_SESSION['user_type']) ? trim($_SESSION['user_type']) : '';

if ($user_id <= 0 || !in_array($user_type, ['individual', 'corporate'])) {
    ob_end_clean();
    header('Location: login.php');
    exit;
}

// Get message ID and validate
$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$view_mode = isset($_GET['view']) && $_GET['view'] === 'sent' ? 'sent' : 'inbox';

if ($message_id <= 0) {
    ob_end_clean();
    header('Location: mailbox.php');
    exit;
}

// Initialize variables
$message = null;
$sender_name = 'Unknown User';
$receiver_name = 'Unknown User';
$message_date = '';
$reply_success = false;
$reply_error = '';

// Ensure messages table exists
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        sender_type ENUM(\'individual\', \'corporate\') NOT NULL,
        receiver_id INT NOT NULL,
        receiver_type ENUM(\'individual\', \'corporate\') NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message_body TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_receiver (receiver_id, receiver_type, is_read),
        INDEX idx_sender (sender_id, sender_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
} catch (PDOException $e) {
    error_log("Error creating messages table: " . $e->getMessage());
    // Continue execution - table might already exist
}

// Fetch message based on view mode with proper security checks
try {
    if ($view_mode === 'sent') {
        // Sent mode: user must be the sender
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE id = ? AND sender_id = ? AND sender_type = ? LIMIT 1');
        $stmt->execute([$message_id, $user_id, $user_type]);
    } else {
        // Inbox mode: user must be the receiver
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE id = ? AND receiver_id = ? AND receiver_type = ? LIMIT 1');
        $stmt->execute([$message_id, $user_id, $user_type]);
    }
    
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Validate message was found
    if (!$message || !is_array($message)) {
        ob_end_clean();
        header('Location: mailbox.php?box=' . $view_mode);
        exit;
    }
    
    // Validate message has required fields
    if (!isset($message['sender_id']) || !isset($message['sender_type']) || 
        !isset($message['receiver_id']) || !isset($message['receiver_type']) ||
        !isset($message['subject']) || !isset($message['message_body']) ||
        !isset($message['created_at'])) {
        error_log("Message data incomplete for ID: " . $message_id);
        ob_end_clean();
        header('Location: mailbox.php?box=' . $view_mode);
        exit;
    }
    
    // Mark as read only if viewing inbox (received message)
    if ($view_mode === 'inbox') {
        try {
            markMessageAsRead($pdo, $message_id);
        } catch (Exception $e) {
            error_log("Error marking message as read: " . $e->getMessage());
            // Continue - not critical
        }
    }
    
    // Get sender and receiver names safely
    try {
        $sender_name = getSenderName($pdo, intval($message['sender_id']), trim($message['sender_type']));
        if (empty($sender_name) || $sender_name === 'Unknown User' || $sender_name === 'Unknown Corporate User') {
            $sender_name = 'Bilinmeyen Kullanıcı';
        }
    } catch (Exception $e) {
        error_log("Error getting sender name: " . $e->getMessage());
        $sender_name = 'Bilinmeyen Kullanıcı';
    }
    
    try {
        $receiver_name = getReceiverName($pdo, intval($message['receiver_id']), trim($message['receiver_type']));
        if (empty($receiver_name) || $receiver_name === 'Unknown User' || $receiver_name === 'Unknown Corporate User') {
            $receiver_name = 'Bilinmeyen Kullanıcı';
        }
    } catch (Exception $e) {
        error_log("Error getting receiver name: " . $e->getMessage());
        $receiver_name = 'Bilinmeyen Kullanıcı';
    }
    
    // Format message date safely
    try {
        if (!empty($message['created_at'])) {
            $message_date = date('d M Y, H:i', strtotime($message['created_at']));
            if ($message_date === false) {
                $message_date = date('d M Y, H:i');
            }
        } else {
            $message_date = date('d M Y, H:i');
        }
    } catch (Exception $e) {
        error_log("Error formatting date: " . $e->getMessage());
        $message_date = date('d M Y, H:i');
    }
    
} catch (PDOException $e) {
    error_log("Error fetching message: " . $e->getMessage());
    ob_end_clean();
    header('Location: mailbox.php?box=' . $view_mode);
    exit;
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    ob_end_clean();
    header('Location: mailbox.php?box=' . $view_mode);
    exit;
}

// Handle reply submission (only for inbox messages)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply']) && $view_mode === 'inbox') {
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message_body = isset($_POST['message_body']) ? trim($_POST['message_body']) : '';
    
    if (empty($subject) || empty($message_body)) {
        $reply_error = 'Konu ve mesaj alanları zorunludur.';
    } elseif (!isset($message['sender_id']) || !isset($message['sender_type'])) {
        $reply_error = 'Geçersiz mesaj bilgisi.';
    } else {
        try {
            // Send reply to the original sender
            $success = sendMessage(
                $pdo,
                intval($message['sender_id']),
                trim($message['sender_type']),
                $subject,
                $message_body
            );
            
            if ($success) {
                $reply_success = true;
                // Clear form data
                $_POST = [];
            } else {
                $reply_error = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
            }
        } catch (Exception $e) {
            error_log("Error sending reply: " . $e->getMessage());
            $reply_error = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}

// Clear output buffer before rendering
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Mesaj Detayı - ASEC</title>
    <link rel="stylesheet" href="css/mailbox.css">
    <link rel="stylesheet" href="css/mobile-optimizations.css">
    <style>
        .message-detail-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .message-detail-header {
            margin-bottom: 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent-color);
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            color: var(--primary-color);
            transform: translateX(-5px);
        }

        .message-detail-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px var(--shadow-light);
            border: 2px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .message-detail-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .message-from {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .message-from-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .message-from-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .message-date-info {
            text-align: right;
            color: var(--text-light);
        }

        .message-date-info i {
            color: var(--accent-color);
            margin-right: 0.5rem;
        }

        .message-subject-detail {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .message-body {
            color: var(--text-color);
            line-height: 1.8;
            font-size: 1rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .reply-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px var(--shadow-light);
            border: 2px solid var(--border-color);
        }

        .reply-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reply-section h3 i {
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(147, 112, 219, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-send {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(147, 112, 219, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .message-detail-container {
                padding: 0 1rem;
            }

            .message-detail-card,
            .reply-section {
                padding: 1.5rem;
            }

            .message-detail-header-info {
                flex-direction: column;
            }

            .message-date-info {
                text-align: left;
            }

            .message-subject-detail {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="mailbox-main">
        <div class="message-detail-container">
            <div class="message-detail-header">
                <a href="mailbox.php?box=<?php echo htmlspecialchars($view_mode); ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i> Mesajlara Dön
                </a>
            </div>

            <?php if ($message && is_array($message)): ?>
            <div class="message-detail-card">
                <div class="message-detail-header-info">
                    <div class="message-from">
                        <?php if ($view_mode === 'sent'): ?>
                            <span class="message-from-label">Alıcı</span>
                            <div class="message-from-name">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($receiver_name); ?>
                                <?php if (isset($message['receiver_type']) && $message['receiver_type'] === 'corporate'): ?>
                                    <span class="sender-badge corporate">Kurumsal</span>
                                <?php else: ?>
                                    <span class="sender-badge individual">Bireysel</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="message-from-label">Gönderen</span>
                            <div class="message-from-name">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($sender_name); ?>
                                <?php if (isset($message['sender_type']) && $message['sender_type'] === 'corporate'): ?>
                                    <span class="sender-badge corporate">Kurumsal</span>
                                <?php else: ?>
                                    <span class="sender-badge individual">Bireysel</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="message-date-info">
                        <i class="fas fa-clock"></i>
                        <?php echo htmlspecialchars($message_date); ?>
                    </div>
                </div>

                <div class="message-subject-detail">
                    <?php echo htmlspecialchars($message['subject'] ?? 'Konu Yok'); ?>
                </div>

                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($message['message_body'] ?? '')); ?>
                </div>
            </div>

            <?php if ($view_mode === 'inbox'): ?>
            <div class="reply-section">
                <h3>
                    <i class="fas fa-reply"></i>
                    Yanıtla
                </h3>

                <?php if ($reply_success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Mesajınız başarıyla gönderildi!
                    </div>
                <?php endif; ?>

                <?php if ($reply_error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($reply_error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="subject">Konu *</label>
                        <input type="text" 
                               id="subject" 
                               name="subject" 
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : 'Re: ' . htmlspecialchars($message['subject'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="message_body">Mesaj *</label>
                        <textarea id="message_body" 
                                  name="message_body" 
                                  required><?php echo isset($_POST['message_body']) ? htmlspecialchars($_POST['message_body']) : ''; ?></textarea>
                    </div>

                    <button type="submit" name="send_reply" class="btn-send">
                        <i class="fas fa-paper-plane"></i>
                        Gönder
                    </button>
                </form>
            </div>
            <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> Mesaj bulunamadı veya erişim yetkiniz yok.
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
