<?php

return [
    'name' => env('DEALER_NAME', '[TÊN ĐẠI LÝ]'),
    'address' => env('DEALER_ADDRESS', '[ĐỊA CHỈ]'),
    'hotline' => env('DEALER_HOTLINE', '[HOTLINE]'),
    'zalo' => env('DEALER_ZALO', '[ZALO]'),
    'email' => env('DEALER_EMAIL', '[EMAIL]'),
    'fanpage' => env('DEALER_FANPAGE', '[LINK]'),
    'maps_url' => env('DEALER_MAPS_URL', '[LINK]'),
    'hours' => env('DEALER_HOURS', '[GIỜ]'),

    'initial_admin' => [
        'name' => env('INITIAL_ADMIN_NAME'),
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],
];
