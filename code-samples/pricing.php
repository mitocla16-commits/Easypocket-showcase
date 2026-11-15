<?php
/*
 * Easypocket - code sample: membership and promotion pricing
 * Excerpt from includes/tienda.php. Shown for portfolio purposes only.
 * Copyright (c) 2016-2026 Fernando. All rights reserved.
 */

/** Is the customer's membership still valid today? */
function membership_active(?array $client): bool
{
    return $client !== null && $client['fecha_fin'] >= date('Y-m-d');
}

/**
 * Works out the unit price a customer pays for a product.
 *   1. An active promotion for the customer's membership wins.
 *   2. Otherwise the membership's % discount is applied.
 * Returns ['unit' => float, 'base' => float, 'promo_id' => ?int, 'label' => ?string]
 */
function price_for(array $product, ?array $client): array
{
    $base   = (float) $product['precio'];
    $result = ['unit' => $base, 'base' => $base, 'promo_id' => null, 'label' => null];

    if (!membership_active($client)) {
        return $result;
    }

    $promo = q(
        'SELECT id_promociones, nombre_promocion, precio FROM promociones
          WHERE id_produ = ? AND id_membresia = ? AND CURDATE() BETWEEN fecha_inicio AND fecha_final
          ORDER BY precio ASC LIMIT 1',
        [$product['id_produ'], $client['id_membre']]
    )->fetch();

    if ($promo && (float) $promo['precio'] < $base) {
        $result['unit']     = (float) $promo['precio'];
        $result['promo_id'] = (int) $promo['id_promociones'];
        $result['label']    = $promo['nombre_promocion'];
    } elseif ((float) $client['descuento'] > 0) {
        $result['unit']  = round($base * (1 - (float) $client['descuento'] / 100), 2);
        $result['label'] = 'Descuento ' . $client['nombre_membre'] . ' (' . rtrim(rtrim($client['descuento'], '0'), '.') . '%)';
    }
    return $result;
}
