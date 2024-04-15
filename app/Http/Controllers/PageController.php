<?php

namespace App\Http\Controllers;

use App\Models\MergedOrg;
use App\Models\NaturalGas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function table(){
        $data = MergedOrg::select(
                        'district_code',
                        DB::raw('SUM(COALESCE(electr_population_volume, 0)) + SUM(COALESCE(electr_pop_nogas_volume, 0)) as electr_population_volume'),
                        DB::raw('SUM(COALESCE(electr_industry_volume, 0)) + SUM(COALESCE(electr_other_volume, 0)) + SUM(COALESCE(electr_budget_volume, 0)) + SUM(COALESCE(electr_public_utilities_volume, 0)) + SUM(COALESCE(electr_сommercial_volume, 0)) + SUM(COALESCE(electr_transport_construction_volume, 0)) + SUM(COALESCE(electr_agriculture_volume, 0)) as electr_industry_volume')
                        )
                        ->whereBetween('date', ['2023-01-01', '2023-12-01'])
                        ->orderBy('district_code')
                        ->groupBy('district_code')
                        ->get();

        // $data = NaturalGas::select(
        //     'district_code',
        //     DB::raw('SUM(COALESCE(population_consump, 0)) as population_consump'),
        //     DB::raw('SUM(COALESCE(budget_consump, 0)) + SUM(COALESCE(heat_consump, 0)) + SUM(COALESCE(sme_consump, 0)) + SUM(COALESCE(sanoat_consump, 0)) as gas_industry_volume')
        //     )
        //     ->whereBetween('date', ['2023-01-01', '2023-12-01'])
        //     ->orderBy('district_code')
        //     ->groupBy('district_code')
        //     ->get();
        return view('table', [
            'data' => $data
        ]);
    }
}
