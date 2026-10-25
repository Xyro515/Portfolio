<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../php/db.php';
$setupError = '';
$loginError = '';
$messages = [];

try {
    $config = portfolio_config();
    $admin = $config['admin'] ?? [];
    $adminUser = (string) ($admin['username'] ?? '');
    $adminHash = (string) ($admin['password_hash'] ?? '');
    if ($adminUser === '' || $adminHash === '' || strpos($adminUser, 'YOUR_') === 0 || strpos($adminHash, 'YOUR_') === 0) {
        throw new RuntimeException('Set secure admin credentials in php/config.local.php before using this page.');
    }

    if (isset($_POST['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: messages.php');
        exit;
    }

    if (isset($_POST['username'], $_POST['password'])) {
        if (hash_equals($adminUser, (string) $_POST['username']) && password_verify((string) $_POST['password'], $adminHash)) {
            session_regenerate_id(true);
            $_SESSION['portfolio_admin'] = true;
            header('Location: messages.php');
            exit;
        }
        $loginError = 'Incorrect username or password.';
    }

    if (!empty($_SESSION['portfolio_admin'])) {
        $messages = portfolio_database()->query(
            'SELECT id, name, email, subject, message, created_at FROM messages ORDER BY created_at DESC'
        )->fetchAll();
    }
} catch (Throwable $error) {
    error_log('Portfolio admin error: ' . $error->getMessage());
    $setupError = $error->getMessage();
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex, nofollow"><title>Portfolio Messages</title><link rel="stylesheet" href="../css/style.css"></head>
<body><main class="section"><div class="container"><p class="eyebrow">Local administration</p><h1 class="admin-title">Portfolio messages</h1>
<?php if ($setupError !== ''): ?>
  <div class="note"><strong>Setup required:</strong> <?= escape($setupError) ?></div>
<?php elseif (empty($_SESSION['portfolio_admin'])): ?>
  <form class="card contact-form admin-login" method="post"><h2>Sign in</h2><?php if ($loginError !== ''): ?><p class="form-status failure"><?= escape($loginError) ?></p><?php endif; ?><div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="button primary" type="submit">Sign in</button></form>
<?php else: ?>
  <form method="post"><button class="button secondary" type="submit" name="logout">Sign out</button></form>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Date</th></tr></thead><tbody><?php foreach ($messages as $item): ?><tr><?php foreach (['id', 'name', 'email', 'subject', 'message', 'created_at'] as $column): ?><td><?= escape((string) $item[$column]) ?></td><?php endforeach; ?></tr><?php endforeach; ?><?php if (!$messages): ?><tr><td colspan="6" class="admin-empty">No messages yet.</td></tr><?php endif; ?></tbody></table></div>
<?php endif; ?></div></main></body></html>
