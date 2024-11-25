<?php
// Include and initialize required classes
require 'image_functions.php';
// require '../vendor/autoload.php'; // Load Intervention Image
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;

// File upload path
$uploadDir = "../images/";

// Allow file formats
$allowTypes = ['jpg', 'png', 'jpeg', 'gif'];

// Set default redirect URL
$redirectURL = 'products';
$errorUpload = '';

if (isset($_POST['imgSubmit'])) {
    // Get submitted data
    $id = $_POST['id'];

    // Check if updating or inserting
    $galleryID = idExists($id) ? $id : null;

    // Handle file uploads
    $fileImages = array_filter($_FILES['images']['name']);
    if (!empty($galleryID) && !empty($fileImages)) {
        foreach ($fileImages as $key => $val) {
            $fileExtension = strtolower(pathinfo($_FILES["images"]["name"][$key], PATHINFO_EXTENSION));
            $newFileName = $id . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;

            if (in_array($fileExtension, $allowTypes)) {
                if (move_uploaded_file($_FILES["images"]["tmp_name"][$key], $targetFilePath)) {
                    try {
                        // Use Intervention Image for processing
                        $image = Image::make($targetFilePath);

                        // Correct orientation
                        $image->orientate();

                        // Resize and compress
                        $image->resize(800, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })->save($targetFilePath, 75);

                        // Insert image data into the database
                        $imgData = [
                            'gallery_id' => $galleryID,
                            'product_id' => $id,
                            'file_name' => $newFileName,
                        ];

                        if (!insertImage($imgData)) {
                            $errorUpload .= "$val | Database insertion failed. ";
                        }
                    } catch (\Exception $e) {
                        $errorUpload .= "$val | Image processing failed: " . $e->getMessage() . " ";
                        @unlink($targetFilePath); // Remove invalid image
                    }
                } else {
                    $errorUpload .= "$val | Upload failed. ";
                }
            } else {
                $errorUpload .= "$val | Invalid file type. ";
            }
        }

        if (!empty($errorUpload)) {
            $_SESSION['error'] = 'Some issues occurred: ' . $errorUpload;
        } else {
            $_SESSION['success'] = 'Gallery images have been uploaded successfully.';
        }
    }
} elseif ($_REQUEST['action_type'] === 'delete' && !empty($_POST['id'])) {
    // Get gallery data
    $prevData = getRows(['where' => ['id' => $_POST['id']], 'return_type' => 'single']);

    // Delete gallery and related images
    if (deleteImage(['gallery_id' => $_POST['id']])) {
        if (!empty($prevData['images'])) {
            foreach ($prevData['images'] as $img) {
                @unlink($uploadDir . $img['file_name']);
            }
        }
        $_SESSION['success'] = 'Gallery has been deleted successfully.';
    } else {
        $_SESSION['error'] = 'Some problem occurred, please try again.';
    }
} elseif ($_POST['action_type'] === 'img_delete' && !empty($_POST['id'])) {
    // Get image data
    $prevData = getImgRow($_POST['id']);

    // Delete image
    if (deleteImage(['id' => $_POST['id']])) {
        @unlink($uploadDir . $prevData['file_name']);
        echo 'ok';
    } else {
        echo 'err';
    }
    exit();
}

// Redirect the user
header("Location: " . $redirectURL);
exit();
