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

    if (!$discountLabel) {
        $pct = 0;
        if ($nights >= 30) $pct = (float)$apt['long_stay_discount_30'];
        elseif ($nights >= 14) $pct = (float)$apt['long_stay_discount_14'];
        elseif ($nights >= 7)  $pct = (float)$apt['long_stay_discount_7'];
        if ($pct > 0) { $discount = $nightlyTotal * ($pct / 100); $discountLabel = "Sconto soggiorno $pct%"; }
    }

    if ($couponPercent > 0) {
        $cd = ($nightlyTotal - $discount) * ($couponPercent / 100);
        $discount += $cd;
        $discountLabel = ($discountLabel ? $discountLabel . ' + ' : '') . "Coupon -{$couponPercent}%";
    }

    $cleaningFee = (float)$apt['cleaning_fee'];
    $taxNights = min($nights, (int)$apt['city_tax_max_nights'] ?: $nights);
    $cityTax = (float)$apt['city_tax'] * max(1, $guests) * $taxNights;
    $subtotal = $nightlyTotal - $discount + $cleaningFee;
    $total = $subtotal + $cityTax;

    return compact('nights','nightlyTotal','cleaningFee','cityTax','discount','discountLabel','subtotal','total','breakdown');
}
