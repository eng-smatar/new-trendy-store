<?php

// Defaults for the admin-editable settings (App\Models\Setting).
return [
    'defaults' => [
        'store_name' => 'ترندي ويندي',
        'main_color' => '#3f2878',
        'ordering_enabled' => true,
        'closed_message' => 'استقبال الطلبات متوقف حالياً.',
        'min_order_amount' => 0,
        'markup_percent' => 20,
        'free_from' => ['default' => 250, 'Jerusalem' => 500, 'Isreal' => 1000],
        'delivery_estimate' => 'التوصيل خلال 2 - 5 أيام',
    ],
];
