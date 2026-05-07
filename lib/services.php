<?php
require_once __DIR__ . '/utils.php';

const SERVICE_TYPES = [
    'car' => ['Auto a noleggio', 'car', 'Auto'],
    'golf_cart' => ['Golf cart', 'caravan', 'Golf cart'],
    'scooter' => ['Scooter', 'bike', 'Scooter'],
    'escooter' => ['Monopattino elettrico', 'zap', 'Monopattino'],
    'boat_excursion' => ['Escursione in barca', 'sailboat', 'Barca'],
    'desert_excursion' => ['Escursione nel deserto', 'mountain', 'Deserto'],
    'diving' => ['Diving / Snorkeling', 'waves', 'Diving'],
    'tour' => ['Tour culturale', 'map', 'Tour'],
    'spa' => ['Spa & relax', 'sparkles', 'Spa'],
    'other_excursion' => ['Altra escursione', 'compass', 'Escursione'],
    'transfer' => ['Transfer aeroporto', 'plane-takeoff', 'Transfer'],
];

const SERVICE_GROUPS = [
    'rental' => ['car', 'golf_cart', 'scooter', 'escooter'],
    'experience' => ['boat_excursion', 'desert_excursion', 'diving', 'tour', 'spa', 'other_excursion'],
    'transfer' => ['transfer'],
];

function serviceTypeLabel(string $type): string {
    return SERVICE_TYPES[$type][0] ?? $type;
}
function serviceTypeIcon(string $type): string {
    return SERVICE_TYPES[$type][1] ?? 'box';
}
function serviceTypeShort(string $type): string {
    return SERVICE_TYPES[$type][2] ?? $type;
}
function serviceGroup(string $type): string {
    foreach (SERVICE_GROUPS as $g => $types) if (in_array($type, $types, true)) return $g;
    return 'other';
}
function isRental(string $type): bool { return in_array($type, SERVICE_GROUPS['rental'], true); }
function isExperience(string $type): bool { return in_array($type, SERVICE_GROUPS['experience'], true); }
function isTransfer(string $type): bool { return $type === 'transfer'; }

function computeRentalQuote(array $svc, string $from, string $to, float $couponPercent = 0): array {
    $days = nightsBetween($from, $to) ?: 1;
    $base = (float)($svc['daily_price'] ?: 0);
    $nightlyTotal = $days * $base;

    $discount = 0; $discountLabel = null;
    if ($days >= 30 && !empty($svc['monthly_price'])) {
        $months = $days / 30;
        $monthlyTotal = $svc['monthly_price'] * $months;
        if ($monthlyTotal < $nightlyTotal) { $discount = $nightlyTotal - $monthlyTotal; $discountLabel = 'Tariffa mensile'; }
    } elseif ($days >= 21 && !empty($svc['triweekly_price']) && $svc['triweekly_price'] < $nightlyTotal) {
        $discount = $nightlyTotal - $svc['triweekly_price']; $discountLabel = '3 settimane';
    } elseif ($days >= 14 && !empty($svc['biweekly_price']) && $svc['biweekly_price'] < $nightlyTotal) {
        $discount = $nightlyTotal - $svc['biweekly_price']; $discountLabel = '2 settimane';
    } elseif ($days >= 7 && !empty($svc['weekly_price']) && $svc['weekly_price'] < $nightlyTotal) {
        $discount = $nightlyTotal - $svc['weekly_price']; $discountLabel = 'Tariffa settimanale';
    }
    if (!$discountLabel) {
        $pct = 0;
        if ($days >= 30) $pct = (float)($svc['long_stay_discount_30'] ?? 0);
        elseif ($days >= 14) $pct = (float)($svc['long_stay_discount_14'] ?? 0);
        elseif ($days >= 7)  $pct = (float)($svc['long_stay_discount_7'] ?? 0);
        if ($pct > 0) { $discount = $nightlyTotal * ($pct / 100); $discountLabel = "Sconto noleggio $pct%"; }
    }
    if ($couponPercent > 0) {
        $cd = ($nightlyTotal - $discount) * ($couponPercent / 100);
        $discount += $cd;
        $discountLabel = ($discountLabel ? $discountLabel . ' + ' : '') . "Coupon -{$couponPercent}%";
    }
    $extras = (float)($svc['cleaning_fee'] ?: 0);
    $subtotal = $nightlyTotal - $discount + $extras;
    return compact('days','nightlyTotal','discount','discountLabel','extras','subtotal') + ['total' => $subtotal];
}

function computeExperienceQuote(array $svc, int $participants, float $couponPercent = 0): array {
    $perPerson = (float)($svc['price_per_person'] ?: 0);
    $perGroup = (float)($svc['price_per_group'] ?: 0);
    $base = $perGroup > 0 ? $perGroup : $perPerson * max(1, $participants);
    $discount = 0; $discountLabel = null;
    if ($couponPercent > 0) {
        $discount = $base * ($couponPercent / 100);
        $discountLabel = "Coupon -{$couponPercent}%";
    }
    $total = $base - $discount;
    return ['participants' => $participants, 'base' => $base, 'discount' => $discount, 'discountLabel' => $discountLabel, 'total' => $total];
}

function computeTransferQuote(array $svc, int $passengers, float $couponPercent = 0): array {
    $perVehicle = (float)($svc['price_per_group'] ?: 0);
    $perPerson = (float)($svc['price_per_person'] ?: 0);
    $base = $perVehicle > 0 ? $perVehicle : $perPerson * max(1, $passengers);
    $discount = 0; $discountLabel = null;
    if ($couponPercent > 0) {
        $discount = $base * ($couponPercent / 100);
        $discountLabel = "Coupon -{$couponPercent}%";
    }
    $total = $base - $discount;
    return ['passengers' => $passengers, 'base' => $base, 'discount' => $discount, 'discountLabel' => $discountLabel, 'total' => $total];
}

function serviceBookingCode(int $seq, string $prefix = 'SV'): string {
    return $prefix . '-' . date('Y') . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
}

function parseFeatures($raw): array {
    if (!$raw) return [];
    $v = json_decode($raw, true);
    return is_array($v) ? $v : [];
}
