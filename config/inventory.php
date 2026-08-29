<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proof-of-concept interface
    |--------------------------------------------------------------------------
    |
    | The POC interface presents the core inventory workflows without removing
    | or disabling any enterprise routes. It defaults to enabled locally and
    | can be explicitly controlled with POC_UI_MODE.
    |
    */
    'poc_ui_mode' => env('POC_UI_MODE', env('APP_ENV', 'production') === 'local'),
];
