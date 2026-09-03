<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    |
    | When false, Swagger UI / generated docs endpoints should return 404.
    | Wired in the OpenAPI branch via L5-Swagger middleware.
    |
    */

    'docs_enabled' => (bool) env('API_DOCS_ENABLED', false),
];
