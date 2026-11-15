<?php
/*
 * Easypocket - code sample: CSRF protection and safe image uploads
 * Excerpt from includes/bootstrap.php. Shown for portfolio purposes only.
 * Copyright (c) 2016-2026 Fernando. All rights reserved.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        flash('error', 'La página expiró, inténtalo de nuevo.');
        $back = $_SERVER['HTTP_REFERER'] ?? base_url('public/index.php');
        header('Location: ' . $back);
        exit;
    }
}

/**
 * Validates and stores an uploaded image. Returns the new file name,
 * null when no file was sent, or throws RuntimeException with a message.
 */
function store_product_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('La imagen no puede pesar más de 3 MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('El formato de la imagen no es válido (JPG, PNG, GIF o WEBP).');
    }
    $name = bin2hex(random_bytes(8)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }
    return $name;
}
