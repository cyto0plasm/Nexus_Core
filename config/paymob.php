<?php
return [
    'api_key'        => env('PAYMOB_API_KEY'),
    'integration_id' => env('PAYMOB_INTEGRATION_ID'),
    'hmac_secret'    => env('PAYMOB_HMAC_SECRET'),
    'base_url'       => 'https://accept.paymob.com/api',
];
