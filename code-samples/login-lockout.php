<?php
/*
 * Easypocket - code sample: customer login with lockout after 3 failed attempts
 * Excerpt from includes/auth.php. Shown for portfolio purposes only.
 * Copyright (c) 2016-2026 Fernando. All rights reserved.
 */

const MAX_LOGIN_ATTEMPTS = 3;

/**
 * Returns null on success or an error message.
 * Three failed attempts block the account until an admin unblocks it.
 */
function client_login(string $email, string $password): ?string
{
    $row = q('SELECT id_cliente, contra, intentos, estado FROM cliente WHERE correo = ?', [$email])->fetch();

    if (!$row) {
        // Verify against a dummy hash so unknown emails take as long as wrong passwords.
        password_verify($password, '$2y$10$7zjoxVDEKIErQNA4rogGXuWQNCyuRTQ2fWhPRzmuNbsBN7P3rZ9IG');
        return 'Correo o contraseña incorrectos.';
    }
    if ((int) $row['estado'] !== 1) {
        return 'Esta cuenta está desactivada. Contacta con el administrador.';
    }
    if ((int) $row['intentos'] >= MAX_LOGIN_ATTEMPTS) {
        return 'Tu cuenta está bloqueada por intentos fallidos. Contacta con el administrador o restablece tu contraseña.';
    }
    if (!password_verify($password, $row['contra'])) {
        $attempts = (int) $row['intentos'] + 1;
        q('UPDATE cliente SET intentos = ? WHERE id_cliente = ?', [$attempts, $row['id_cliente']]);
        $left = MAX_LOGIN_ATTEMPTS - $attempts;
        return $left > 0
            ? "Correo o contraseña incorrectos. Te quedan $left intento(s)."
            : 'Has superado el número de intentos. Tu cuenta ha sido bloqueada.';
    }

    if (password_needs_rehash($row['contra'], PASSWORD_DEFAULT)) {
        q('UPDATE cliente SET contra = ? WHERE id_cliente = ?', [password_hash($password, PASSWORD_DEFAULT), $row['id_cliente']]);
    }
    q('UPDATE cliente SET intentos = 0 WHERE id_cliente = ?', [$row['id_cliente']]);

    session_regenerate_id(true);
    $_SESSION['cliente_id'] = (int) $row['id_cliente'];
    return null;
}

function require_admin(?string $permission = null): array
{
    $admin = current_admin();
    if (!$admin) {
        flash('warning', 'Inicia sesión para entrar al panel.');
        redirect('admin/login.php');
    }
    if ($permission !== null && !admin_can($permission)) {
        flash('error', 'No tienes permiso para entrar a esa sección.');
        redirect('admin/index.php');
    }
    return $admin;
}
