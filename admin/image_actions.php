<?php
// echo "</br></br></br>";

// Include and initialize DB class 
require 'image_functions.php';

// File upload path 
$uploadDir = "../images/";

// Allow file formats 
$allowTypes = array('jpg', 'png', 'jpeg', 'gif');

// Set default redirect url 
// $redirectURL = 'products';


if (isset($_POST['imgSubmit'])) {

    // Set redirect url 
    // $redirectURL = 'products';

    // Get submitted data 
    $title    = $_POST['title'];
    $id        = $_POST['id'];

    
    // Submitted user data 
    $galData = array(
        'title'  => $title
    );

    
    // ID query string 
    $idStr = !empty($id) ? '?id=' . $id : '';
    if (empty($title)) {
        $_SESSION['error'] = 'Enter the gallery title.';
    } else {
        $uploadSuccess = true; // Variable to track successful uploads
        $errorMsg = '';
        if (idExists($id)) {
            // Update data 
            print_r($galData);
            $update = update($galData, array('id' => $id));
            $galleryID = $id;
        } else {
            // Insert data 
            print_r($galData);
            $galData = array(
                'title'  => $title,
                'product_id' => $id
            );
            $insert = insert($galData);
            $galleryID = $insert;
        }


        $fileImages = array_filter($_FILES['images']['name']);
        if (!empty($galleryID) && !empty($fileImages)) {
            if (!empty($fileImages)) {
                foreach ($fileImages as $key => $val) {
                    // File upload path 
                    $fileExtension = pathinfo($_FILES["images"]["name"][$key], PATHINFO_EXTENSION);
                    $newFileName = $id . '_' . time() . '_' . uniqid() . '.' . $fileExtension;

                    $targetFilePath = $uploadDir . $newFileName;

                    // Check whether file type is valid 
                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                    if (in_array($fileType, $allowTypes)) {
                        // Upload file to server 
                        if (move_uploaded_file($_FILES["images"]["tmp_name"][$key], $targetFilePath)) {
                            // Image db insert 
                            $imgData = array(
                                'gallery_id' => $galleryID,
                                'product_id' => $id,
                                'file_name' => $newFileName
                            );
                            $insert = insertImage($imgData);
                        } else {
                            $errorUpload .= $fileImages[$key] . ' | ';
                            $uploadSuccess = false;
                        }
                    } else {
                        $errorUploadType .= $fileImages[$key] . ' | ';
                        $uploadSuccess = false;
                    }
                }

                $errorUpload = !empty($errorUpload) ? 'Upload Error: ' . trim($errorUpload, ' | ') : '';
                $errorUploadType = !empty($errorUploadType) ? 'File Type Error: ' . trim($errorUploadType, ' | ') : '';

                if (!$uploadSuccess) {
                    $errorMsg = '<br/>' . ($errorUpload ? $errorUpload . '<br/>' : '') . $errorUploadType;
                }
            }

            if (!$uploadSuccess) {
                $_SESSION['error'] = 'There were some issues:' . $errorMsg;
            } else {
                $_SESSION['success'] = 'Gallery images have been uploaded successfully.';
            }
            // $redirectURL = 'products';
        } else {
            $_SESSION['error'] = 'Some problem occurred, please try again.';
            // Set redirect url 
            // $redirectURL .= $idStr;
        }
    }

    
} elseif (($_REQUEST['action_type'] == 'block') && !empty($_POST['id'])) {
    // Update data 
    $galData = array('status' => 0);
    $condition = array('id' => $_POST['id']);
    $update = update($galData, $condition);
    if ($update) {
        // $statusType = 'success';
        $_SESSION['success'] =  'Gallery data has been blocked successfully.';
    } else {
        $_SESSION['error'] = 'Some problem occurred, please try again.';
    }

    
} elseif (($_REQUEST['action_type'] == 'unblock') && !empty($_POST['id'])) {
    // Update data 
    $galData = array('status' => 1);
    $condition = array('id' => $_POST['id']);
    $update = update($galData, $condition);
    if ($update) {
        $_SESSION['success'] = 'Gallery data has been activated successfully.';
    } else {
        $_SESSION['error'] = 'Some problem occurred, please try again.';
    }

    
} elseif (($_REQUEST['action_type'] == 'delete') && !empty($_POST['id'])) {
    // Previous image files 
    $conditions['where'] = array(
        'id' => $_POST['id'],
    );
    $conditions['return_type'] = 'single';
    $prevData = getRows($conditions);

    // Delete gallery data 
    $condition = array('id' => $_POST['id']);
    $delete = delete($condition);
    if ($delete) {
        // Delete images data 
        $condition = array('gallery_id' => $_POST['id']);
        $delete = deleteImage($condition);

        // Remove files from server 
        if (!empty($prevData['images'])) {
            foreach ($prevData['images'] as $img) {
                @unlink($uploadDir . $img['file_name']);
            }
        }

        $_SESSION['success'] = 'Gallery has been deleted successfully.';
    } else {
        $_SESSION['error'] = 'Some problem occurred, please try again.';
    }

    
} elseif (($_POST['action_type'] == 'img_delete') && !empty($_POST['id'])) {
    // Previous image data 
    $prevData = getImgRow($_POST['id']);

    // Delete gallery data 
    $condition = array('id' => $_POST['id']);
    $delete = deleteImage($condition);
    if ($delete) {
        @unlink($uploadDir . $prevData['file_name']);
        $status = 'ok';
    } else {
        $status  = 'err';
    }
    echo $status;
    die;
}

// Store status into the session 
// $_SESSION['sessData'] = $sessData;

// Redirect the user 
// header("Location: " . $redirectURL);
exit();
