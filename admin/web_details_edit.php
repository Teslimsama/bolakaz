<?php
include 'session.php';

if (isset($_POST['edit'])) {
    // Sanitize input data
    $id = htmlspecialchars($_POST['id']);
    $site_name = htmlspecialchars($_POST['site_name']);
    $site_number = htmlspecialchars($_POST['site_number']);
    $site_email = htmlspecialchars($_POST['site_email']);
    $site_address = $_POST['edit_site_address'];
    $short_description = $_POST['short_desc'];
    $desc = $_POST['desc'];

    $conn = $pdo->open();

    try {
        // Check if web details exist for the given ID
        $stmt = $conn->prepare("SELECT * FROM web_details WHERE id=:id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['error'] = 'Web details not found';
        } else {
            // Update web details in the database
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
            $_SESSION['success'] = 'Web details updated successfully';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    $pdo->close();
} else {
    $_SESSION['error'] = 'Fill up the edit form first';
}

header('Location: web_details.php'); // Replace with the correct redirection path
exit;
