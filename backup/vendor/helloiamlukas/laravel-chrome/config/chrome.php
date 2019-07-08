<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chrome Path
    |--------------------------------------------------------------------------
    |
    | Manually set the path where Google Chrome is installed.
    |
    */
    'exec_path' => '/Applications/MAMP/htdocs/aff-dev/node_modules/puppeteer/.local-chromium/mac-641577/chrome-mac',
    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    |
    | Change the user agent that will be used by Google Chrome.
    |
    */
    'user_agent' => null,
    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Specify a timeout in seconds.
    | (null = no timeout)
    |
    */
    'timeout' => 10,
    /*
    |--------------------------------------------------------------------------
    | Viewport
    |--------------------------------------------------------------------------
    |
    | Specify a viewport.
    |
    */
    'viewport' =>[
                    'width' => 1920,
                    'height' => 1080
                ],
    /*
    |--------------------------------------------------------------------------
    | Blacklist
    |--------------------------------------------------------------------------
    |
    | Specify a list of files that should not be loaded.
    |
    */
    'blacklist' => [
                    'www.google-analytics.com',
                    'analytics.js'
                ],
    /*
    |--------------------------------------------------------------------------
    | Additional Request Headers
    |--------------------------------------------------------------------------
    |
    | Specify additional headers.
    |
    */
    'headers' => [],
];
