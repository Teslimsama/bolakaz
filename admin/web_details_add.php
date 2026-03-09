<?php
include 'session.php'; // Ensure your database connection is properly configured

if (isset($_POST['add'])) {
    // Sanitize input data
    $site_name = htmlspecialchars($_POST['site_name']);
    $site_number = htmlspecialchars($_POST['site_number']);
    $site_email = htmlspecialchars($_POST['site_email']);
    $site_address = $_POST['site_address'];
    $short_desc = $_POST['short_desc'];
    $desc = $_POST['desc'];

    $conn = $pdo->open();

    try {
        // Check if the site details already exist
        $stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM web_details WHERE site_name=:site_name");
        $stmt->execute(['site_name' => $site_name]);
        $row = $stmt->fetch();

        if ($row['numrows'] > 0) {
            $_SESSION['error'] = 'Site details already exist';
        } else {
            // Insert web details into the database
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
            $_SESSION['success'] = 'Web details added successfully';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    $pdo->close();
} else {
    $_SESSION['error'] = 'Fill up the web details form first';
}

header('Location: web_details.php'); // Replace with the correct redirection path
exit;
