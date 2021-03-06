<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    /**
     * @param Request $request
     * @param string  $cron_token
     * @return ResponseFactory|Response
     */
    public function __invoke(Request $request, $cron_token)
    {
        // Verify the cron token
        if ($cron_token !== systemsettings('cron_token')) {
            return response(trans('settings.cron_token_auth_failure'), 403);
        }

        Artisan::call('schedule:run');

        return response(trans('settings.cron_execute_successful'));
    }
}
