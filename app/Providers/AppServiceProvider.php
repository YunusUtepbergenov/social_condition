<?php

namespace App\Providers;
use App\Models\TableName;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $records = TableName::select('merged_name', 'column_uz')->get();
        $translates = [];
        foreach($records as $record){
            $translates[$record->merged_name] = $record->column_uz;
        }
        view()->share('translates', $translates);
    }
}
