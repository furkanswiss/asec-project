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

// Get receiver information from GET parameters
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
$receiver_type = isset($_GET['receiver_type']) ? trim($_GET['receiver_type']) : '';
$prefill_subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';

// Validate receiver_type
if (!in_array($receiver_type, ['individual', 'corporate'])) {
    $receiver_type = '';
}

// Get receiver name for display
$receiver_name = '';
$receiver_valid = false;

if ($receiver_id > 0 && $receiver_type) {
    try {
        $receiver_name = getSenderName($pdo, $receiver_id, $receiver_type);
        // Verify receiver exists
        if ($receiver_type === 'corporate') {
            $stmt = $pdo->prepare('SELECT id FROM corporate_users WHERE id = ?');
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        }
        $stmt->execute([$receiver_id]);
        if ($stmt->fetch()) {
            $receiver_valid = true;
        }
    } catch (PDOException $e) {
        error_log("Error validating receiver: " . $e->getMessage());
    }
}

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message_body = trim($_POST['message_body'] ?? '');
    $form_receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $form_receiver_type = isset($_POST['receiver_type']) ? trim($_POST['receiver_type']) : '';
    
    // Validate form data
    if (empty($subject) || empty($message_body)) {
        $error = 'Konu ve mesaj alanları zorunludur.';
    } elseif ($form_receiver_id <= 0 || !in_array($form_receiver_type, ['individual', 'corporate'])) {
        $error = 'Geçersiz alıcı bilgisi.';
    } elseif ($form_receiver_id == $user_id && $form_receiver_type == $user_type) {
        $error = 'Kendinize mesaj gönderemezsiniz.';
    } else {
        // Send message
        $success = sendMessage($pdo, $form_receiver_id, $form_receiver_type, $subject, $message_body);
        
        if ($success) {
            // Redirect to mailbox after successful send
            header('Location: mailbox.php?sent=1');
            exit;
        } else {
            $error = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
    
    // Update receiver info from form if validation failed
    if (!$success && $form_receiver_id > 0) {
        $receiver_id = $form_receiver_id;
        $receiver_type = $form_receiver_type;
        $receiver_name = getSenderName($pdo, $receiver_id, $receiver_type);
        $receiver_valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Mesaj Gönder - ASEC</title>
    <link rel="stylesheet" href="css/mailbox.css">
    <link rel="stylesheet" href="css/mobile-optimizations.css">
    <style>
        .compose-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .compose-header {
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

        .compose-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px var(--shadow-light);
            border: 2px solid var(--border-color);
        }

        .compose-card h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .compose-card h2 i {
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
            min-height: 200px;
            resize: vertical;
        }

        .form-group input[type="text"][readonly] {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .receiver-info {
            background: var(--unread-bg);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--unread-border);
        }

        .receiver-info strong {
            color: var(--primary-color);
        }

        .receiver-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 0.5rem;
        }

        .receiver-badge.corporate {
            background: linear-gradient(135deg, #6a0dad, #9370db);
            color: white;
        }

        .receiver-badge.individual {
            background: linear-gradient(135deg, #3498db, #5dade2);
            color: white;
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

        .btn-send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        .no-receiver {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .compose-container {
                padding: 0 1rem;
            }

            .compose-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="mailbox-main">
        <div class="compose-container">
            <div class="compose-header">
                <a href="mailbox.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Mesajlara Dön
                </a>
            </div>

            <div class="compose-card">
                <h2>
                    <i class="fas fa-paper-plane"></i>
                    Yeni Mesaj Gönder
                </h2>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$receiver_valid && !isset($_POST['send_message'])): ?>
                    <div class="no-receiver">
                        <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;"></i>
                        <p>Geçersiz alıcı bilgisi veya alıcı belirtilmemiş.</p>
                        <a href="mailbox.php" class="btn-send" style="margin-top: 1rem; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Mesajlara Dön
                        </a>
                    </div>
                <?php else: ?>
                    <?php if ($receiver_valid): ?>
                        <div class="receiver-info">
                            <strong>Alıcı:</strong> <?php echo htmlspecialchars($receiver_name); ?>
                            <span class="receiver-badge <?php echo $receiver_type; ?>">
                                <?php echo $receiver_type === 'corporate' ? 'Kurumsal' : 'Bireysel'; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
                        <input type="hidden" name="receiver_type" value="<?php echo htmlspecialchars($receiver_type); ?>">

                        <div class="form-group">
                            <label for="subject">Konu *</label>
                            <input type="text" 
                                   id="subject" 
                                   name="subject" 
                                   value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : htmlspecialchars($prefill_subject); ?>" 
                                   required
                                   placeholder="Mesaj konusunu girin">
                        </div>

                        <div class="form-group">
                            <label for="message_body">Mesaj *</label>
                            <textarea id="message_body" 
                                      name="message_body" 
                                      required
                                      placeholder="Mesajınızı buraya yazın..."><?php echo isset($_POST['message_body']) ? htmlspecialchars($_POST['message_body']) : ''; ?></textarea>
                        </div>

                        <button type="submit" name="send_message" class="btn-send" <?php echo !$receiver_valid ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i>
                            Gönder
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>

