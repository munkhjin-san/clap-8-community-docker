<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project full-access users
    |--------------------------------------------------------------------------
    | User IDs (comma-separated in the env var) who can access every project
    | regardless of manager/member/director assignment or position.
    */
    'project_full_access_user_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('PROJECT_FULL_ACCESS_USER_IDS', '608,610'))
    ))),

    'actual_result_admin_user_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('ACTUAL_RESULT_ADMIN_USER_IDS', '608,610'))
    ))),

    'actual_result_payroll_user_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('ACTUAL_RESULT_PAYROLL_USER_IDS', '608,610'))
    ))),

];
