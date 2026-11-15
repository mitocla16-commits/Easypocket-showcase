<?php
/*
 * Easypocket - code sample: simulated PayPal approval step (request handling only)
 * Excerpt from public/paypal.php. Shown for portfolio purposes only.
 * Copyright (c) 2016-2026 Fernando. All rights reserved.
 */

$client = require_client();
$sim    = $_SESSION['paypal_sim'] ?? null;
$token  = (string) ($_GET['token'] ?? '');

if (!$sim || !hash_equals($sim['token'], $token) || time() - $sim['created'] > 15 * 60) {
    unset($_SESSION['paypal_sim']);
    flash('warning', 'La sesión de pago con PayPal expiró. Inténtalo de nuevo.');
    redirect('public/checkout.php');
}

$summary = cart_summary($client);

if (is_post()) {
    csrf_check();

    if (post('accion') === 'cancelar') {
        unset($_SESSION['paypal_sim']);
        flash('info', 'Cancelaste el pago con PayPal. Tu carrito sigue intacto.');
        redirect('public/checkout.php');
    }

    $email = post('paypal_correo');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Escribe un correo válido para tu cuenta PayPal.');
        redirect('public/paypal.php?token=' . $token);
    }
    if (cart_fingerprint($summary) !== $sim['fingerprint']) {
        unset($_SESSION['paypal_sim']);
        flash('warning', 'Tu carrito cambió durante el pago. Revisa el total y vuelve a pagar.');
        redirect('public/checkout.php');
    }

    try {
        $orderId = create_order($client, ['metodo' => 'paypal', 'paypal_correo' => $email]);
        unset($_SESSION['paypal_sim']);
        flash('success', '¡Pago con PayPal aprobado! Gracias por tu compra.');
        redirect('public/pedido.php?id=' . $orderId);
    } catch (RuntimeException $e) {
        unset($_SESSION['paypal_sim']);
        flash('error', $e->getMessage());
        redirect('public/carrito.php');
    }
}

// ... HTML for the approval page omitted
