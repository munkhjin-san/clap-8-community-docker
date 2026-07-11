<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Language Lines
    |--------------------------------------------------------------------------
    |
    | User-facing error messages returned by the global exception handler
    | (App\Exceptions\Handler). Missing records and forbidden records are
    | intentionally collapsed into the same message to avoid leaking whether
    | a given record exists.
    |
    */

    // When the record could be identified (has an ID)
    'record_not_found' => 'The record with ID :id does not exist, or you do not have permission to access it.',

    // When no ID is available (firstOrFail, 403, etc.)
    'record_forbidden_or_missing' => 'The record does not exist, or you do not have permission to access it.',

];
