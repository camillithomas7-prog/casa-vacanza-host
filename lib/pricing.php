<?php
require_once __DIR__ . '/utils.php';

function isWeekend(int $ts): bool {
    $dow = (int)date('N', $ts); // 1=Mon, 7=Sun
    return $dow === 5 || $dow === 6;
}

function pickRulePrice(int $ts, array $rules): ?float {
    $matching = [];
    foreach ($rules as $r) {
        $s = strtotime($r['start_date']); $e = strtotime($r['end_date']);
        if ($s <= $ts && $ts < $e) $matching[] = $r;
    }
    if (!$matching) return null;
    usort($matching, fn($a, $b) => $b['priority'] - $a['priority']);
    return (float)$matching[0]['price_per_night'];
}

function computeQuote(array $apt, array $rules, string $checkIn, string $checkOut, int $guests, float $couponPercent = 0): array {
    $nights = nightsBetween($checkIn, $checkOut);
    $weekly = (float)($apt['weekly_price'] ?? 0);

    if (isWeeklyOnly() && $weekly > 0 && in_array($nights, [7,14,21,30], true)) {
        // ── MODALITÀ SOLO SETTIMANALE ──
        // Riferimento: prezzo proporzionale alla tariffa settimanale.
        // "Risparmio" = differenza tra riferimento e prezzo pacchetto.
        $nightlyTotal = $weekly * ($nights / 7); // valore teorico se moltiplicassi a settimane
        $package = $weekly; $packageLabel = '1 settimana';
        if ($nights === 14) {
            $package = !empty($apt['biweekly_price']) ? (float)$apt['biweekly_price'] : $weekly * 2;
            $packageLabel = '2 settimane';
        } elseif ($nights === 21) {
            $package = !empty($apt['triweekly_price']) ? (float)$apt['triweekly_price'] : $weekly * 3;
            $packageLabel = '3 settimane';
        } elseif ($nights === 30) {
            $package = !empty($apt['monthly_price']) ? (float)$apt['monthly_price'] : $weekly * (30/7);
            $packageLabel = '1 mese';
        }
        $savings = max(0, round($nightlyTotal - $package, 2));
        $discount = $savings;
        $discountLabel = $savings > 0 ? "Risparmio {$packageLabel}" : null;
        $packageBase = $package; // base da cui partono coupon/fee

        if ($couponPercent > 0) {
            $cd = $packageBase * ($couponPercent / 100);
            $discount += $cd;
            $discountLabel = ($discountLabel ? $discountLabel . ' + ' : '') . "Coupon -{$couponPercent}%";
            $packageBase -= $cd;
        }

        $cleaningFee = (float)$apt['cleaning_fee'];
        $cityTax = 0; // Tassa di soggiorno rimossa dal sistema
        $subtotal = $packageBase + $cleaningFee;
        $total = $subtotal;
        $securityDeposit = round($subtotal * (securityDepositPct() / 100), 2);
        $breakdown = [];

        return compact('nights','nightlyTotal','cleaningFee','cityTax','discount','discountLabel','subtotal','total','breakdown','savings','packageLabel','securityDeposit');
    }

    // ── MODALITÀ CLASSICA (notte/weekend) ──
    $days = daysRange($checkIn, $checkOut);
    $breakdown = [];
    $nightlyTotal = 0;
    foreach ($days as $ts) {
        $ruled = pickRulePrice($ts, $rules);
        $price = $ruled ?? (isWeekend($ts) && !empty($apt['weekend_price']) ? (float)$apt['weekend_price'] : (float)$apt['base_price']);
        $breakdown[] = ['date' => date('Y-m-d', $ts), 'price' => $price];
        $nightlyTotal += $price;
    }

    $discount = 0; $discountLabel = null;

    if ($nights >= 30 && !empty($apt['monthly_price'])) {
        $months = $nights / 30;
        $monthlyTotal = $apt['monthly_price'] * $months;
        if ($monthlyTotal < $nightlyTotal) { $discount = $nightlyTotal - $monthlyTotal; $discountLabel = 'Tariffa mensile'; }
    } elseif ($nights >= 21 && !empty($apt['triweekly_price']) && $apt['triweekly_price'] < $nightlyTotal) {
        $discount = $nightlyTotal - $apt['triweekly_price']; $discountLabel = '3 settimane';
    } elseif ($nights >= 14 && !empty($apt['biweekly_price']) && $apt['biweekly_price'] < $nightlyTotal) {
        $discount = $nightlyTotal - $apt['biweekly_price']; $discountLabel = '2 settimane';
    } elseif ($nights >= 7 && !empty($apt['weekly_price']) && $apt['weekly_price'] < $nightlyTotal) {
        $discount = $nightlyTotal - $apt['weekly_price']; $discountLabel = 'Tariffa settimanale';
    }

    if ($couponPercent > 0) {
        $cd = ($nightlyTotal - $discount) * ($couponPercent / 100);
        $discount += $cd;
        $discountLabel = ($discountLabel ? $discountLabel . ' + ' : '') . "Coupon -{$couponPercent}%";
    }

    $cleaningFee = (float)$apt['cleaning_fee'];
    $cityTax = 0; // Tassa di soggiorno rimossa
    $subtotal = $nightlyTotal - $discount + $cleaningFee;
    $total = $subtotal;
    $securityDeposit = round($subtotal * (securityDepositPct() / 100), 2);

    return compact('nights','nightlyTotal','cleaningFee','cityTax','discount','discountLabel','subtotal','total','breakdown','securityDeposit');
}
