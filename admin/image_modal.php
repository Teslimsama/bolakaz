<?php
require_once 'image_functions.php';

header('Content-Type: application/json; charset=UTF-8');

$productId = (int)($_POST['id'] ?? 0);
if ($productId <= 0) {
    echo json_encode([
        'success' => false,
        'html' => '',
        'message' => 'Invalid product selected.',
    ]);
    exit;
}

$conditions = [
    'where' => ['id' => $productId],
    'return_type' => 'single',
];
$data = getRows($conditions);
$product = (is_array($data) && !empty($data)) ? $data[0] : null;

if (!$product) {
    echo json_encode([
        'success' => false,
        'html' => '',
        'message' => 'Product not found.',
    ]);
    exit;
}

$productName = trim((string)($product['name'] ?? ('product-' . $productId)));
$mainPhoto = trim((string)($product['photo'] ?? ''));
$csrfToken = function_exists('app_get_csrf_token') ? app_get_csrf_token() : '';

$html = '';
$html .= '<div id="imageModalStatus" class="alert" style="display:none;"></div>';
$html .= '<form id="galleryUploadForm" method="post" action="image_actions.php" enctype="multipart/form-data">';
$html .= '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
$html .= '<input type="hidden" name="action_type" value="img_upload">';
$html .= '<input type="hidden" name="id" value="' . (int)$productId . '">';
$html .= '<input type="hidden" name="name" value="' . htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') . '">';
$html .= '<div class="form-group">';
$html .= '<label>Gallery images (multiple):</label>';
$html .= '<input type="file" name="images[]" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" multiple required>';
$html .= '<p class="help-block">Allowed: JPG, JPEG, PNG, GIF, WEBP. Max 5MB each.</p>';
$html .= '</div>';
$html .= '<button type="submit" name="imgSubmit" class="btn btn-success btn-flat" id="galleryUploadBtn">Upload</button>';
$html .= '</form>';

$html .= '<hr>';
$html .= '<div class="row image-gallery-grid">';

if ($mainPhoto !== '') {
    $html .= '<div class="col-xs-12 col-sm-6">';
    $html .= '<div class="image-card image-card-main">';
    $html .= '<img class="image-card-img" src="../images/' . htmlspecialchars($mainPhoto, ENT_QUOTES, 'UTF-8') . '" alt="Main image">';
    $html .= '<div class="image-card-meta"><small class="text-muted">Main image (managed in Single Edit)</small></div>';
    $html .= '</div>';
    $html .= '</div>';
}

$galleryImages = $product['images'] ?? [];
if (!empty($galleryImages)) {
    foreach ($galleryImages as $image) {
        $imageId = (int)($image['id'] ?? 0);
        $fileName = (string)($image['file_name'] ?? '');
        if ($imageId <= 0 || $fileName === '') {
            continue;
        }

        $html .= '<div class="col-xs-12 col-sm-6" id="imgb_' . $imageId . '">';
        $html .= '<div class="image-card">';
        $html .= '<img class="image-card-img" src="../images/' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . '" alt="Gallery image">';
        $html .= '<div class="image-card-meta">';
        $html .= '<button type="button" class="btn btn-danger btn-xs btn-flat image-delete-btn" data-id="' . $imageId . '"><i class="fa fa-trash"></i> Delete</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
} else {
    $html .= '<div class="col-xs-12"><p class="text-muted">No gallery images yet.</p></div>';
}

$html .= '</div>';

echo json_encode([
    'success' => true,
    'html' => $html,
    'message' => '',
]);
