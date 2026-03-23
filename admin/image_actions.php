<?php
require 'image_functions.php';
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/lib/image_tools.php';
include 'slugify.php';

$uploadDir = "../images/";
$allowTypes = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
$allowMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024;
$redirectURL = 'products';

function image_actions_is_ajax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function image_actions_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

function image_actions_validate_file(array $file, int $index, array $allowTypes, array $allowMime, int $maxFileSize): string
{
    $name = (string)($file['name'][$index] ?? '');
    $tmp = (string)($file['tmp_name'][$index] ?? '');
    $size = (int)($file['size'][$index] ?? 0);

    if ($name === '' || $tmp === '' || !is_uploaded_file($tmp)) {
        return 'Invalid upload payload.';
    }
    if ($size <= 0) {
        return $name . ' | Empty file.';
    }
    if ($size > $maxFileSize) {
        return $name . ' | File exceeds 5MB limit.';
    }

    $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowTypes, true)) {
        return $name . ' | Invalid file type.';
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $tmp);
            finfo_close($finfo);
        }
    }
    if ($mime !== '' && !in_array($mime, $allowMime, true)) {
        return $name . ' | Invalid image mime type.';
    }

    return '';
}

$isUploadRequest = isset($_POST['imgSubmit']) || (isset($_POST['action_type']) && $_POST['action_type'] === 'img_upload');

if ($isUploadRequest) {
    $id = (int)($_POST['id'] ?? 0);
    $galleryID = ($id > 0 && idExists($id)) ? $id : null;
    $fileImages = (isset($_FILES['images']['name']) && is_array($_FILES['images']['name'])) ? array_filter($_FILES['images']['name']) : [];
    $errors = [];
    $uploadedCount = 0;

    if (empty($galleryID)) {
        $errors[] = 'Invalid product selected for gallery upload.';
    } elseif (empty($fileImages)) {
        $errors[] = 'Please select at least one image to upload.';
    } else {
        foreach ($fileImages as $key => $val) {
            $validationError = image_actions_validate_file($_FILES['images'], $key, $allowTypes, $allowMime, $maxFileSize);
            if ($validationError !== '') {
                $errors[] = $validationError;
                continue;
            }

            $fileExtension = strtolower((string)pathinfo($_FILES["images"]["name"][$key], PATHINFO_EXTENSION));
            $name = (string)($_POST['name'] ?? ('product-' . $id));
            $slug = slugify($name);
            $newFileName = $slug . '_' . $id . '_' . uniqid() . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;

            if (!move_uploaded_file($_FILES["images"]["tmp_name"][$key], $targetFilePath)) {
                $errors[] = $val . ' | Upload failed.';
                continue;
            }

            $optimizationError = '';
            $optimized = app_optimize_image($targetFilePath, 800, 75, $optimizationError);
            if (!$optimized && function_exists('app_log')) {
                app_log('warning', 'Gallery image optimization skipped; keeping original upload.', [
                    'file' => $val,
                    'product_id' => $id,
                    'error' => $optimizationError !== '' ? $optimizationError : 'Native image optimizer could not process the upload.',
                ]);
            }

            if (!is_file($targetFilePath)) {
                $errors[] = $val . ' | Upload failed.';
                continue;
            }

            $imgData = [
                'gallery_id' => $galleryID,
                'product_id' => $id,
                'file_name' => $newFileName,
            ];

            if (!insertImage($imgData)) {
                $errors[] = $val . ' | Database insertion failed.';
                @unlink($targetFilePath);
                continue;
            }

            $uploadedCount++;
            if (!$optimized) {
                $_SESSION['warning'] = 'Some images were uploaded without optimization due to server image-processing limits.';
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
    } else {
        $_SESSION['success'] = ($uploadedCount > 0)
            ? 'Gallery images uploaded successfully.'
            : 'No images were uploaded.';
    }

    if (image_actions_is_ajax()) {
        if (!empty($errors)) {
            image_actions_json([
                'success' => false,
                'message' => implode(' ', $errors),
                'uploaded' => $uploadedCount,
            ], 422);
        }

        $successMessage = (string)($_SESSION['success'] ?? 'Upload successful.');
        if (!empty($_SESSION['warning'])) {
            $successMessage .= ' ' . (string)$_SESSION['warning'];
        }

        image_actions_json([
            'success' => true,
            'message' => $successMessage,
            'uploaded' => $uploadedCount,
        ]);
    }
} elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'img_delete') {
    $imgId = (int)($_POST['id'] ?? 0);
    $prevData = ($imgId > 0) ? getImgRow($imgId) : false;

    if (!$prevData) {
        if (image_actions_is_ajax()) {
            image_actions_json(['success' => false, 'message' => 'Image not found.'], 404);
        }
        $_SESSION['error'] = 'Image not found.';
        header("Location: " . $redirectURL);
        exit();
    }

    if (deleteImage(['id' => $imgId])) {
        @unlink($uploadDir . (string)$prevData['file_name']);
        if (image_actions_is_ajax()) {
            image_actions_json(['success' => true, 'message' => 'Image removed successfully.']);
        }
        $_SESSION['success'] = 'Image removed successfully.';
    } else {
        if (image_actions_is_ajax()) {
            image_actions_json(['success' => false, 'message' => 'Unable to remove image.'], 500);
        }
        $_SESSION['error'] = 'Unable to remove image.';
    }
} elseif (isset($_REQUEST['action_type']) && $_REQUEST['action_type'] === 'delete' && !empty($_POST['id'])) {
    $prevData = getRows(['where' => ['id' => (int)$_POST['id']], 'return_type' => 'single']);
    $gallery = (is_array($prevData) && !empty($prevData)) ? $prevData[0] : null;

    if (deleteImage(['gallery_id' => (int)$_POST['id']])) {
        if (!empty($gallery['images'])) {
            foreach ($gallery['images'] as $img) {
                @unlink($uploadDir . (string)($img['file_name'] ?? ''));
            }
        }
        $_SESSION['success'] = 'Gallery has been deleted successfully.';
    } else {
        $_SESSION['error'] = 'Some problem occurred, please try again.';
    }
} elseif (image_actions_is_ajax()) {
    image_actions_json(['success' => false, 'message' => 'Invalid request.'], 400);
}

header("Location: " . $redirectURL);
exit();
