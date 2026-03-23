<?php
include 'session.php';
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
    $id = (int)($_POST['id'] ?? 0);
    $site_name = trim((string)($_POST['site_name'] ?? ''));
    $site_number = trim((string)($_POST['site_number'] ?? ''));
    $site_email = trim((string)($_POST['site_email'] ?? ''));
    $site_address = normalize_rich_text((string)($_POST['edit_site_address'] ?? ''));
    $short_description = normalize_rich_text((string)($_POST['short_desc'] ?? ''));
    $desc = normalize_rich_text((string)($_POST['desc'] ?? ''));

    if ($id <= 0 || $site_name === '' || $site_number === '' || $site_email === '' || !filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please provide valid web details';
        header('Location: web_details.php');
        exit;
    }

    $conn = $pdo->open();

    try {
        // Check if web details exist for the given ID
        $stmt = $conn->prepare("SELECT * FROM web_details WHERE id=:id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['error'] = 'Web details not found';
        } else {
            $conn->beginTransaction();
            $stmt = $conn->prepare("UPDATE web_details 
                                    SET site_name=:site_name, site_number=:site_number, site_email=:site_email, 
                                        site_address=:site_address, short_description=:short_description, description=:description 
                                    WHERE id=:id");
            $stmt->execute([
                'site_name' => $site_name,
                'site_number' => $site_number,
                'site_email' => $site_email,
                'site_address' => $site_address,
                'short_description' => $short_description,
                'description' => $desc,
                'id' => $id,
            ]);
            sync_enqueue_or_fail($conn, 'web_details', $id);
            $conn->commit();
            $_SESSION['success'] = 'Web details updated successfully';
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
