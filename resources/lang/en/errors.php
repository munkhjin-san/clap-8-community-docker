<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Language Lines
    |--------------------------------------------------------------------------
    |
    | User-facing error messages returned by the global exception handler
    | (bootstrap/app.php withExceptions). Missing records and forbidden records
    | are intentionally collapsed into the same message to avoid leaking whether
    | a given record exists.
    |
    */

    // When the record could be identified (has an ID)
    'record_not_found' => 'The record with ID :id does not exist, or you do not have permission to access it.',

    // When no ID is available (firstOrFail, 403, etc.)
    'record_forbidden_or_missing' => 'The record does not exist, or you do not have permission to access it.',

    // Unauthenticated (401)
    'unauthenticated' => 'Please log in to continue.',

    // Too many requests (429)
    'too_many_requests' => 'Too many requests. Please wait a moment and try again.',

    // Server error (5xx, production only)
    'server_error' => 'A server error occurred. Please try again in a moment.',

];
