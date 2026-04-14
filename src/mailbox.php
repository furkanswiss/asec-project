<?php
require_once 'db.php';
require_once 'includes/messages.php';
require_once 'includes/lang.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

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
}

// Determine which box to show (inbox or sent)
$box = isset($_GET['box']) ? trim($_GET['box']) : 'inbox';
if (!in_array($box, ['inbox', 'sent'])) {
    $box = 'inbox';
}

// Fetch messages based on box type
$messages = [];
try {
    if ($box === 'inbox') {
        // Inbox: messages where current user is the receiver
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE receiver_id = ? AND receiver_type = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id, $user_type]);
    } else {
        // Sent: messages where current user is the sender
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE sender_id = ? AND sender_type = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id, $user_type]);
    }
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
}

// Get unread count (only for inbox)
$unread_count = getUnreadMessageCount($pdo);

// Check for success message from compose page
$message_sent = isset($_GET['sent']) && $_GET['sent'] == '1';
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Mesajlarım - ASEC</title>
    <link rel="stylesheet" href="css/mailbox.css">
    <link rel="stylesheet" href="css/mobile-optimizations.css">
    <style>
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success i {
            color: #155724;
        }

        /* Tab Navigation */
        .mailbox-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-color);
        }

        .mailbox-tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mailbox-tab:hover {
            color: var(--primary-color);
        }

        .mailbox-tab.active {
            color: var(--accent-color);
            border-bottom-color: var(--accent-color);
            font-weight: 600;
        }

        .mailbox-tab i {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .mailbox-tabs {
                flex-direction: column;
                border-bottom: none;
            }

            .mailbox-tab {
                border-bottom: 2px solid var(--border-color);
                border-left: 3px solid transparent;
                padding-left: 1rem;
            }

            .mailbox-tab.active {
                border-left-color: var(--accent-color);
                border-bottom-color: var(--border-color);
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="mailbox-main">
        <div class="mailbox-container">
            <div class="mailbox-header">
                <h2 class="page-title">
                    <i class="fas fa-envelope"></i> Mesajlarım
                </h2>
                <?php if ($box === 'inbox' && $unread_count > 0): ?>
                    <div class="unread-badge">
                        <span><?php echo $unread_count; ?> Okunmamış Mesaj</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Navigation -->
            <div class="mailbox-tabs">
                <a href="?box=inbox" class="mailbox-tab <?php echo $box === 'inbox' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i>
                    Gelen Kutusu
                    <?php if ($box === 'inbox' && $unread_count > 0): ?>
                        <span style="background: #dc3545; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?box=sent" class="mailbox-tab <?php echo $box === 'sent' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane"></i>
                    Gönderilenler
                </a>
            </div>

            <?php if ($message_sent): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    Mesajınız başarıyla gönderildi!
                </div>
            <?php endif; ?>

            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <i class="fas fa-<?php echo $box === 'inbox' ? 'inbox' : 'paper-plane'; ?>"></i>
                    <h3><?php echo $box === 'inbox' ? 'Gelen kutunuz boş' : 'Gönderilen mesajınız yok'; ?></h3>
                    <p><?php echo $box === 'inbox' ? 'Size mesaj geldiğinde burada görünecektir.' : 'Henüz hiç mesaj göndermediniz.'; ?></p>
                </div>
            <?php else: ?>
                <div class="messages-list">
                    <?php foreach ($messages as $message): ?>
                        <?php
                        if ($box === 'inbox') {
                            // Inbox: show sender information
                            $person_name = getSenderName($pdo, $message['sender_id'], $message['sender_type']);
                            $person_type = $message['sender_type'];
                            $is_unread = !$message['is_read'];
                            $detail_url = 'message-detail.php?id=' . $message['id'];
                        } else {
                            // Sent: show receiver information
                            $person_name = getReceiverName($pdo, $message['receiver_id'], $message['receiver_type']);
                            $person_type = $message['receiver_type'];
                            $is_unread = false; // Sent messages are always considered "read"
                            // For sent messages, we need to check if we can view them
                            // Since message-detail.php only shows received messages, we'll create a view-only version
                            $detail_url = 'message-detail.php?id=' . $message['id'] . '&view=sent';
                        }
                        $message_date = date('d M Y, H:i', strtotime($message['created_at']));
                        ?>
                        <a href="<?php echo htmlspecialchars($detail_url); ?>" class="message-card <?php echo $is_unread ? 'unread' : ''; ?>">
                            <div class="message-card-header">
                                <div class="message-sender">
                                    <i class="fas fa-user"></i>
                                    <strong><?php echo $person_name; ?></strong>
                                    <span style="color: var(--text-light); font-weight: normal; margin-left: 0.5rem;">
                                        (<?php echo $box === 'inbox' ? 'Gönderen' : 'Alıcı'; ?>)
                                    </span>
                                    <?php if ($person_type === 'corporate'): ?>
                                        <span class="sender-badge corporate">Kurumsal</span>
                                    <?php else: ?>
                                        <span class="sender-badge individual">Bireysel</span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-date">
                                    <i class="fas fa-clock"></i>
                                    <?php echo $message_date; ?>
                                </div>
                            </div>
                            <div class="message-subject">
                                <i class="fas fa-envelope<?php echo $is_unread ? '-open' : ''; ?>"></i>
                                <?php echo htmlspecialchars($message['subject']); ?>
                                <?php if ($is_unread): ?>
                                    <span class="unread-indicator"></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-preview">
                                <?php 
                                $preview = htmlspecialchars($message['message_body']);
                                if (strlen($preview) > 150) {
                                    $preview = substr($preview, 0, 150) . '...';
                                }
                                echo $preview;
                                ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
