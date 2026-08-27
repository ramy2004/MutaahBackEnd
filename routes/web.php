<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // This deploy is an API only — no frontend assets are built here, so the
    // default welcome view could never render: it calls @vite([...]) and there
    // is no public/build/manifest.json, which threw a 500 on every request to /.
    //
    // That 500 was worse than a cosmetic broken page. Laravel renders the error
    // page OUTSIDE the CORS middleware, so the response carried no
    // Access-Control-Allow-Origin header, and the browser reported it as a CORS
    // failure rather than a server error — sending us looking for a CORS bug
    // that did not exist. (The real API routes under /api/v1 were returning
    // correct CORS headers the whole time.)
    //
    // A tiny JSON status is the right thing for an API root: it answers 200, it
    // goes through the normal middleware stack so it carries CORS headers, and
    // it doubles as a health check for uptime monitoring.
    return response()->json([
        'service' => config('app.name', 'Mutaah API'),
        'status'  => 'ok',
        'api'     => url('/api/v1'),
    ]);
});
