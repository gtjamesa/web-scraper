<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cookies
    |--------------------------------------------------------------------------
    |
    | Cookies will be stored as websites are scraped to maintain the logged
    | status. You can make scraping sessions stateless or store on a per-user
    | basis, which allows users to supply login details and websites can then
    | be scraped on their behalf.
    |
    */
    'cookies' => [
        'stateless' => false,
        'encrypt' => true,
        'directory' => storage_path('cookies'),
        'fileName' => '{domain}-{username}.dat',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    |
    | Configure the User Agent to be used by all configured scrapers.
    |
    */
    'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:62.0) Gecko/20100101 Firefox/62.0',

];
