<?php

if (!function_exists('app_uploaded_image_is_valid')) {
    function app_uploaded_image_is_valid(string $path, array $allowMime = []): bool
    {
        if ($path === '' || !is_file($path)) {
            return false;
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $path);
                finfo_close($finfo);
            }
        }

        if (!empty($allowMime) && $mime !== '' && !in_array($mime, $allowMime, true)) {
            return false;
        }

        return @getimagesize($path) !== false;
    }
}

if (!function_exists('app_upload_error_message')) {
    function app_upload_error_message(int $errorCode, string $fieldLabel = 'Image'): string
    {
        $label = trim($fieldLabel) !== '' ? trim($fieldLabel) : 'Image';

        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return $label . ' is too large.';
            case UPLOAD_ERR_PARTIAL:
                return $label . ' upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server upload temp folder is missing.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server could not write the uploaded file.';
            case UPLOAD_ERR_EXTENSION:
                return $label . ' upload was blocked by a server extension.';
            case UPLOAD_ERR_NO_FILE:
                return $label . ' is required.';
            default:
                return 'Unable to process the uploaded image.';
        }
    }
}

if (!function_exists('app_store_uploaded_image')) {
    function app_store_uploaded_image(array $file, array $options = [], string &$error = ''): ?string
    {
        $defaults = [
            'required' => true,
            'field_label' => 'Image',
            'upload_dir' => '',
            'filename_prefix' => 'image_',
            'max_size' => 5 * 1024 * 1024,
            'allow_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'allow_mime' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ];
        $config = array_merge($defaults, $options);
        $error = '';

        $uploadError = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            if ((bool)$config['required']) {
                $error = app_upload_error_message($uploadError, (string)$config['field_label']);
                return null;
            }

            return '';
        }

        if ($uploadError !== UPLOAD_ERR_OK) {
            $error = app_upload_error_message($uploadError, (string)$config['field_label']);
            return null;
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $error = 'Invalid upload payload.';
            return null;
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            $error = 'Uploaded image is empty.';
            return null;
        }
        if ($size > (int)$config['max_size']) {
            $error = app_upload_error_message(UPLOAD_ERR_INI_SIZE, (string)$config['field_label']);
            return null;
        }

        $name = (string)($file['name'] ?? '');
        $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, (array)$config['allow_types'], true)) {
            $error = 'Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.';
            return null;
        }

        if (!app_uploaded_image_is_valid($tmp, (array)$config['allow_mime'])) {
            $error = 'File is not an image.';
            return null;
        }

        $uploadDir = trim((string)$config['upload_dir']);
        if ($uploadDir === '') {
            $error = 'Upload directory is not configured.';
            return null;
        }
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $error = 'Upload directory is not writable.';
            return null;
        }

        $normalizedDir = rtrim($uploadDir, "\\/");
        $filename = uniqid((string)$config['filename_prefix'], true) . '.' . $ext;
        $target = $normalizedDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $target)) {
            $error = 'Failed to upload image.';
            return null;
        }

        return $filename;
    }
}

if (!function_exists('app_image_optimize_rotate')) {
    function app_image_optimize_rotate($image, int $angle)
    {
        if (!function_exists('imagerotate')) {
            return $image;
        }

        $rotated = @imagerotate($image, $angle, 0);
        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);
        return $rotated;
    }
}

if (!function_exists('app_image_optimize_flip')) {
    function app_image_optimize_flip($image, int $mode)
    {
        if (!function_exists('imageflip')) {
            return $image;
        }

        if (@imageflip($image, $mode) === false) {
            return $image;
        }

        return $image;
    }
}

if (!function_exists('app_image_fix_orientation')) {
    function app_image_fix_orientation($image, string $path, int $imageType)
    {
        if ($imageType !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int)($exif['Orientation'] ?? 1);

        switch ($orientation) {
            case 2:
                return app_image_optimize_flip($image, IMG_FLIP_HORIZONTAL);
            case 3:
                return app_image_optimize_rotate($image, 180);
            case 4:
                return app_image_optimize_flip($image, IMG_FLIP_VERTICAL);
            case 5:
                $image = app_image_optimize_rotate($image, -90);
                return app_image_optimize_flip($image, IMG_FLIP_HORIZONTAL);
            case 6:
                return app_image_optimize_rotate($image, -90);
            case 7:
                $image = app_image_optimize_rotate($image, 90);
                return app_image_optimize_flip($image, IMG_FLIP_HORIZONTAL);
            case 8:
                return app_image_optimize_rotate($image, 90);
            default:
                return $image;
        }
    }
}

if (!function_exists('app_image_create_resource')) {
    function app_image_create_resource(string $path, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false;
            case IMAGETYPE_PNG:
                return function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false;
            case IMAGETYPE_GIF:
                return function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false;
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            default:
                return false;
        }
    }
}

if (!function_exists('app_image_create_canvas')) {
    function app_image_create_canvas(int $width, int $height, int $imageType)
    {
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            return false;
        }

        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        } else {
            $background = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $background);
        }

        return $canvas;
    }
}

if (!function_exists('app_image_write_resource')) {
    function app_image_write_resource($image, string $path, int $imageType, int $quality): bool
    {
        $quality = max(0, min(100, $quality));

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                if (function_exists('imageinterlace')) {
                    @imageinterlace($image, true);
                }
                return @imagejpeg($image, $path, $quality);
            case IMAGETYPE_PNG:
                $compression = (int)round((100 - $quality) * 9 / 100);
                $compression = max(0, min(9, $compression));
                return @imagepng($image, $path, $compression);
            case IMAGETYPE_GIF:
                return @imagegif($image, $path);
            case IMAGETYPE_WEBP:
                return function_exists('imagewebp') ? @imagewebp($image, $path, $quality) : false;
            default:
                return false;
        }
    }
}

if (!function_exists('app_optimize_image')) {
    function app_optimize_image(string $path, int $maxWidth = 1200, int $quality = 80, ?string &$error = null): bool
    {
        $error = '';

        if ($path === '' || !is_file($path)) {
            $error = 'Image file was not found.';
            return false;
        }

        if (!extension_loaded('gd')) {
            $error = 'GD extension is not available.';
            return false;
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            $error = 'Uploaded file is not a valid image.';
            return false;
        }

        $imageType = (int)($imageInfo[2] ?? 0);
        if ($imageType === IMAGETYPE_GIF) {
            $error = 'GIF uploads are kept as-is to preserve animation.';
            return false;
        }

        $source = app_image_create_resource($path, $imageType);
        if ($source === false) {
            $error = 'Image format is not supported by the native optimizer.';
            return false;
        }

        $source = app_image_fix_orientation($source, $path, $imageType);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);
            $error = 'Image dimensions are invalid.';
            return false;
        }

        $targetWidth = $sourceWidth;
        $targetHeight = $sourceHeight;
        if ($maxWidth > 0 && $sourceWidth > $maxWidth) {
            $targetWidth = $maxWidth;
            $targetHeight = max(1, (int)round($sourceHeight * ($targetWidth / $sourceWidth)));
        }

        $output = app_image_create_canvas($targetWidth, $targetHeight, $imageType);
        if ($output === false) {
            imagedestroy($source);
            $error = 'Unable to allocate image canvas.';
            return false;
        }

        if (@imagecopyresampled($output, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight) === false) {
            imagedestroy($output);
            imagedestroy($source);
            $error = 'Unable to resize uploaded image.';
            return false;
        }

        $written = app_image_write_resource($output, $path, $imageType, $quality);

        imagedestroy($output);
        imagedestroy($source);

        if (!$written) {
            $error = 'Unable to save optimized image.';
            return false;
        }

        return true;
    }
}
