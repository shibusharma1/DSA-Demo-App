<?php
// config/erpnext.php
return [
    'base_url'      => env('ERPNEXT_BASE_URL', 'https://manjit.frappe.cloud'),
    'client_id'     => env('ERPNEXT_CLIENT_ID'),
    'client_secret' => env('ERPNEXT_CLIENT_SECRET'),
    'redirect_uri'  => env('ERPNEXT_REDIRECT_URI', 'http://127.0.0.1:8000/erpnext/callback'),
    'scopes'        => 'openid all',
];