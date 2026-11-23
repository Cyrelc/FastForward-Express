<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketing Site URL
    |--------------------------------------------------------------------------
    |
    | Where the "back to main site" links on the login and password reset
    | pages go. While the public pages are served by this app it is /home;
    | once they move to their own host, set MARKETING_URL to that host
    | (e.g. https://fastforwardexpress.ca).
    |
    */

    'marketing_url' => env('MARKETING_URL', '/home'),
];
