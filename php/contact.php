<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, bool $success, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Only POST requests are accepted.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    respond(422, false, 'Name must contain between 2 and 100 characters.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    respond(422, false, 'Please provide a valid email address.');
}
if (mb_strlen($subject) < 3 || mb_strlen($subject) > 200) {
    respond(422, false, 'Subject must contain between 3 and 200 characters.');
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
    respond(422, false, 'Message must contain between 10 and 5,000 characters.');
}

try {
    require_once __DIR__ . '/db.php';
    $statement = portfolio_database()->prepare(
        'INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)'
    );
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
    ]);
    respond(201, true, 'Thanks! Your message has been saved successfully.');
} catch (Throwable $error) {
    error_log('Portfolio contact error: ' . $error->getMessage());
    respond(503, false, 'Message storage is unavailable. Please try again later.');
}
