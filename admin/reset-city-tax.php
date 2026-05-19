<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
requireAdmin();

q('UPDATE apartments SET city_tax = 0, city_tax_max_nights = 0, security_deposit = 0');
flash('Tassa di soggiorno e cauzione fissa azzerate su tutti gli appartamenti. La cauzione ora viene calcolata automaticamente al ' . (int)setting('security_deposit_pct', '20') . '%.');
redirect('/admin/appartamenti.php');
