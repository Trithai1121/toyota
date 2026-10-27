<?php

return [
    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
        'capi_access_token' => env('META_CAPI_ACCESS_TOKEN'),
        'graph_api_version' => env('META_GRAPH_API_VERSION'),
    ],
    'ga4_id' => env('GA4_MEASUREMENT_ID'),
    'gtm_id' => env('GTM_CONTAINER_ID'),
];
