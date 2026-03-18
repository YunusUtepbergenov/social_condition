<?php

namespace App\Providers;

use App\Models\TableName;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $records = TableName::select('merged_name', 'column_uz')->get();
        $translates = [];
        foreach($records as $record){
            $translates[$record->merged_name] = $record->column_uz;
        }
        view()->share('translates', $translates);
    }
}
