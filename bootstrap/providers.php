<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\FortifyServiceProvider::class, // (community_logic) Sanctum/Fortify auth migration
    // App\Providers\BroadcastServiceProvider::class, // broadcasting disabled
    // RouteServiceProvider folded into bootstrap/app.php withRouting().
    // Maatwebsite\Excel\ExcelServiceProvider is registered via package auto-discovery.
];
