<?php
/**
 * Messages Helper Functions
 * Functions for handling mailbox/messaging system
 */

// Include email queue helper for notifications
require_once __DIR__ . '/email_queue_helper.php';

/**
 * Get unread message count for the currently logged-in user
 * @param PDO $pdo Database connection
 * @return int Number of unread messages
 */
function getUnreadMessageCount($pdo) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return 0;
    }
    
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND receiver_type = ? AND is_read = 0');
        $stmt->execute([$user_id, $user_type]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error getting unread message count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get sender name based on sender type and ID
 * @param PDO $pdo Database connection
 * @param int $sender_id Sender ID
 * @param string $sender_type Sender type ('individual' or 'corporate')
 * @return string Sender name
 */
function getSenderName($pdo, $sender_id, $sender_type) {
    try {
        if ($sender_type === 'corporate') {
            $stmt = $pdo->prepare('SELECT company_name FROM corporate_users WHERE id = ?');
            $stmt->execute([$sender_id]);
            $result = $stmt->fetch();
            return $result ? htmlspecialchars($result['company_name']) : 'Unknown Corporate User';
        } else {
            $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt->execute([$sender_id]);
            $result = $stmt->fetch();
            return $result ? htmlspecialchars($result['name']) : 'Unknown User';
        }
    } catch (PDOException $e) {
        error_log("Error getting sender name: " . $e->getMessage());
        return 'Unknown User';
    }
}

/**
 * Get receiver name based on receiver type and ID
 * @param PDO $pdo Database connection
 * @param int $receiver_id Receiver ID
 * @param string $receiver_type Receiver type ('individual' or 'corporate')
 * @return string Receiver name
 */
function getReceiverName($pdo, $receiver_id, $receiver_type) {
    try {
        if ($receiver_type === 'corporate') {
            $stmt = $pdo->prepare('SELECT company_name FROM corporate_users WHERE id = ?');
            $stmt->execute([$receiver_id]);
            $result = $stmt->fetch();
            return $result ? htmlspecialchars($result['company_name']) : 'Unknown Corporate User';
        } else {
            $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt->execute([$receiver_id]);
            $result = $stmt->fetch();
            return $result ? htmlspecialchars($result['name']) : 'Unknown User';
        }
    } catch (PDOException $e) {
        error_log("Error getting receiver name: " . $e->getMessage());
        return 'Unknown User';
    }
}

/**
 * Send a message
 * @param PDO $pdo Database connection
 * @param int $receiver_id Receiver ID
 * @param string $receiver_type Receiver type ('individual' or 'corporate')
 * @param string $subject Message subject
 * @param string $message_body Message body
 * @return bool Success status
 */
function sendMessage($pdo, $receiver_id, $receiver_type, $subject, $message_body) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    $sender_id = $_SESSION['user_id'];
    $sender_type = $_SESSION['user_type'];
    
    // Validate that sender and receiver are different
    if ($sender_id == $receiver_id && $sender_type == $receiver_type) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, subject, message_body) VALUES (?, ?, ?, ?, ?, ?)');
        $success = $stmt->execute([$sender_id, $sender_type, $receiver_id, $receiver_type, $subject, $message_body]);
        
        if ($success) {
            // Queue notification for direct message
            $message_id = $pdo->lastInsertId();
            
            // Get recipient information
            $recipient_name = getReceiverName($pdo, $receiver_id, $receiver_type);
            $recipient_email = '';
            try {
                if ($receiver_type === 'corporate') {
                    $stmt_email = $pdo->prepare('SELECT email FROM corporate_users WHERE id = ?');
                    $stmt_email->execute([$receiver_id]);
                    $recipient_result = $stmt_email->fetch();
                    $recipient_email = $recipient_result ? $recipient_result['email'] : '';
                } else {
                    $stmt_email = $pdo->prepare('SELECT email FROM users WHERE id = ?');
                    $stmt_email->execute([$receiver_id]);
                    $recipient_result = $stmt_email->fetch();
                    $recipient_email = $recipient_result ? $recipient_result['email'] : '';
                }
            } catch (PDOException $e) {
                error_log("Error fetching recipient email: " . $e->getMessage());
            }
            
            // Get sender name
            $sender_name = getSenderName($pdo, $sender_id, $sender_type);
            
            // Construct message URL
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base_path = dirname($_SERVER['PHP_SELF']);
            $message_url = $protocol . '://' . $host . $base_path . '/message-detail.php?id=' . $message_id;
            
            // Queue the notification if we have recipient email
            if (!empty($recipient_email)) {
                queueDirectMessageNotification($pdo, $recipient_email, $recipient_name, $sender_name, $message_url);
            }
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log("Error sending message: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark message as read
 * @param PDO $pdo Database connection
 * @param int $message_id Message ID
 * @return bool Success status
 */
function markMessageAsRead($pdo, $message_id) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    
    try {
        // Only mark as read if the current user is the receiver
        $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ? AND receiver_type = ?');
        return $stmt->execute([$message_id, $user_id, $user_type]);
    } catch (PDOException $e) {
        error_log("Error marking message as read: " . $e->getMessage());
        return false;
    }
}

