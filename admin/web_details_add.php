<?php
include 'session.php'; // Ensure your database connection is properly configured
require_once __DIR__ . '/../lib/sync.php';

function normalize_rich_text(string $value): string
{
    $decoded = $value;
    for ($i = 0; $i < 3; $i++) {
        $next = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
        if ($next === $decoded) {
            break;
        }
        $decoded = $next;
    }
    return trim($decoded);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $site_name = trim((string)($_POST['site_name'] ?? ''));
    $site_number = trim((string)($_POST['site_number'] ?? ''));
    $site_email = trim((string)($_POST['site_email'] ?? ''));
    $site_address = normalize_rich_text((string)($_POST['site_address'] ?? ''));
    $short_desc = normalize_rich_text((string)($_POST['short_desc'] ?? ''));
    $desc = normalize_rich_text((string)($_POST['desc'] ?? ''));

    if ($site_name === '' || $site_number === '' || $site_email === '' || !filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please provide valid web details';
        header('Location: web_details.php');
        exit;
    }

    $conn = $pdo->open();

    try {
        // Check if the site details already exist
        $stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM web_details WHERE site_name=:site_name");
        $stmt->execute(['site_name' => $site_name]);
        $row = $stmt->fetch();

        if ($row['numrows'] > 0) {
            $_SESSION['error'] = 'Site details already exist';
        } else {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO web_details (site_name, site_number, site_email, site_address, short_description, description) 
                                    VALUES (:site_name, :site_number, :site_email, :site_address, :short_desc, :desc)");
            $stmt->execute([
                'site_name' => $site_name,
                'site_number' => $site_number,
                'site_email' => $site_email,
                'site_address' => $site_address,
                'short_desc' => $short_desc,
                'desc' => $desc,
            ]);
            $webDetailId = (int) $conn->lastInsertId();
            sync_enqueue_or_fail($conn, 'web_details', $webDetailId);
            $conn->commit();
            $_SESSION['success'] = 'Web details added successfully';
        }
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }

    $pdo->close();
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('Location: web_details.php'); // Replace with the correct redirection path
exit;
