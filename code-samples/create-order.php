<?php
/*
 * Easypocket - code sample: turning the cart into an order inside one transaction
 * Excerpt from includes/tienda.php. Shown for portfolio purposes only.
 * Copyright (c) 2016-2026 Fernando. All rights reserved.
 */

/**
 * Turns the cart into an order inside one transaction:
 * locks the product rows, re-checks stock and prices, stores the sale and
 * its lines and takes the units out of stock.
 *
 * $payment = ['metodo' => 'tarjeta'|'paypal', 'marca' => .., 'ultimos4' => .., 'paypal_correo' => ..]
 * Returns the new order id. Throws RuntimeException if something is no longer available.
 */
function create_order(array $client, array $payment): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $lines = [];
        $subtotal = $total = 0.0;

        foreach (cart() as $id => $qty) {
            $p = q('SELECT * FROM productos WHERE id_produ = ? FOR UPDATE', [$id])->fetch();
            if (!$p) {
                throw new RuntimeException('Un producto de tu carrito ya no existe.');
            }
            if ((int) $p['cantidad_produc'] < $qty) {
                throw new RuntimeException("No hay suficientes unidades de {$p['nombre_produc']} (quedan {$p['cantidad_produc']}).");
            }
            $price = price_for($p, $client);
            $line  = round($price['unit'] * $qty, 2);
            $lines[] = [$p['id_produ'], $qty, $price['unit'], $line, $price['promo_id']];
            $subtotal += $price['base'] * $qty;
            $total    += $line;
        }
        if (!$lines) {
            throw new RuntimeException('Tu carrito está vacío.');
        }

        q(
            'INSERT INTO venta (id_cliente, subtotal, descuento, total, metodo_pago, referencia,
                                tarjeta_marca, tarjeta_ultimos4, paypal_correo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $client['id_cliente'], round($subtotal, 2), round($subtotal - $total, 2), round($total, 2),
                $payment['metodo'], new_reference($payment['metodo']),
                $payment['marca'] ?? null, $payment['ultimos4'] ?? null, $payment['paypal_correo'] ?? null,
            ]
        );
        $orderId = (int) $pdo->lastInsertId();

        foreach ($lines as [$productId, $qty, $unit, $line, $promoId]) {
            q(
                'INSERT INTO detalleventa (id_venta, id_producto, cantidad, precio_unitario, total_compra, id_promociones)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$orderId, $productId, $qty, $unit, $line, $promoId]
            );
            q('UPDATE productos SET cantidad_produc = cantidad_produc - ? WHERE id_produ = ?', [$qty, $productId]);
        }

        $pdo->commit();
        cart_clear();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
