<?php
/*
 * Easypocket - code sample: simulated card payment
 * Excerpt from includes/tienda.php. Shown for portfolio purposes only.
 * Copyright (c) 2016-2026 Fernando. All rights reserved.
 */

function luhn_valid(string $digits): bool
{
    $sum = 0;
    $double = false;
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = (int) $digits[$i];
        if ($double) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $double = !$double;
    }
    return $digits !== '' && $sum % 10 === 0;
}

function card_brand(string $digits): string
{
    return match (true) {
        (bool) preg_match('/^4/', $digits)                    => 'Visa',
        (bool) preg_match('/^(5[1-5]|2[2-7])/', $digits)      => 'Mastercard',
        (bool) preg_match('/^3[47]/', $digits)                => 'American Express',
        (bool) preg_match('/^6(011|5)/', $digits)             => 'Discover',
        default                                               => 'Tarjeta',
    };
}

/**
 * Validates the card form and simulates the bank's answer.
 * Test numbers (same idea as Stripe's test cards):
 *   4242 4242 4242 4242  -> approved
 *   5555 5555 5555 4444  -> approved (Mastercard)
 *   4000 0000 0000 0002  -> declined
 *   4000 0000 0000 9995  -> declined, insufficient funds
 * Any other number that passes the Luhn check is approved.
 *
 * Returns ['ok' => bool, 'error' => ?string, 'brand' => string, 'last4' => string]
 */
function simulate_card_payment(string $holder, string $number, string $expiry, string $cvc): array
{
    $digits = card_digits($number);
    $brand  = card_brand($digits);
    $fail   = fn(string $msg) => ['ok' => false, 'error' => $msg, 'brand' => $brand, 'last4' => substr($digits, -4)];

    if (mb_strlen($holder) < 3) {
        return $fail('Escribe el nombre del titular como aparece en la tarjeta.');
    }
    if (strlen($digits) < 13 || strlen($digits) > 19 || !luhn_valid($digits)) {
        return $fail('El número de tarjeta no es válido.');
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', $expiry, $m)) {
        return $fail('La fecha de vencimiento debe tener el formato MM/AA.');
    }
    $lastDay = (new DateTime(sprintf('20%s-%s-01', $m[2], $m[1])))->modify('last day of this month')->format('Y-m-d');
    if ($lastDay < date('Y-m-d')) {
        return $fail('La tarjeta está vencida.');
    }
    $cvcLength = $brand === 'American Express' ? 4 : 3;
    if (!preg_match('/^\d{' . $cvcLength . '}$/', $cvc)) {
        return $fail("El código de seguridad (CVC) debe tener $cvcLength dígitos.");
    }
    if ($digits === '4000000000000002') {
        return $fail('El banco rechazó la tarjeta. Prueba con otra tarjeta.');
    }
    if ($digits === '4000000000009995') {
        return $fail('Fondos insuficientes. Prueba con otra tarjeta.');
    }
    return ['ok' => true, 'error' => null, 'brand' => $brand, 'last4' => substr($digits, -4)];
}
