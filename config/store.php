<?php

// Defaults for the admin-editable settings (App\Models\Setting).
return [
    // Mart category paths linked in the footer: shoes, watches, fragrances.
    'footer_categories' => ['clothing & accessories > shoes & footwear', 'jewelry & watches > watches', 'health & beauty > beauty > fragrances'],

    'defaults' => [
        'store_name' => 'ترندي ويندي',
        'footer_text' => 'قطع أزياء ولايف ستايل مختارة للاستخدام اليومي، تُشحن في جميع أنحاء المنطقة مع الدفع عند الاستلام.',
        'social_links' => [
            ['label' => 'Instagram', 'url' => 'https://www.instagram.com/trendy.windy.79'],
            ['label' => 'Facebook', 'url' => 'https://www.facebook.com/Trendy.Windy.Online'],
            ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@trendy.windy.online'],
        ],
        'main_color' => '#3f2878',
        'ordering_enabled' => true,
        'closed_message' => 'استقبال الطلبات متوقف حالياً.',
        'min_order_amount' => 0,
        'markup_percent' => 20,
        'free_from' => ['default' => 250, 'Jerusalem' => 500, 'Isreal' => 1000],
        'delivery_estimate' => 'التوصيل خلال 2 - 5 أيام',
    ],
];
