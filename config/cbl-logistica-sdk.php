<?php

return [
    /*
     * Every CBL call goes to one host; the token endpoint lives on the same host
     * but is split out so a proxy can be put in front of either one.
     */
    'base_url' => env('CBL_LOGISTICA_BASE_URL', 'https://clientesws.cbl-logistica.com/api/v1.0'),
    'token_url' => env('CBL_LOGISTICA_TOKEN_URL', 'https://clientesws.cbl-logistica.com/api/v1.0'),

    'http' => [
        'timeout' => 30,
        'verify' => true,
    ],

    /*
     * The daily token is cached until the end of the day it was issued. Tokens are
     * keyed per account, so any number of accounts may share one store.
     */
    'cache' => [
        'store' => env('CBL_LOGISTICA_CACHE_STORE'),
        'prefix' => 'cbl-logistica:daily-token',
    ],

    /*
     * A single-account convenience for standalone use. Applications serving several
     * CBL accounts pass a Credentials object per call instead and never read these.
     */
    'credentials' => [
        'username' => env('CBL_LOGISTICA_USERNAME'),
        'password' => env('CBL_LOGISTICA_PASSWORD'),
        'client_token' => env('CBL_LOGISTICA_CLIENT_TOKEN'),
        'client_code' => env('CBL_LOGISTICA_CLIENT_CODE'),
    ],
];
