
<?php

use App\Models\District;
use App\Models\Region;

if (!function_exists('findRegion')) {
    function findRegion($regionCode)
    {
        return Region::where('code', $regionCode)->first()->name;
    }
}

if (!function_exists('findDistrict')) {
    function findDistrict($districtCode)
    {
        return District::where('code', $districtCode)->first()->name;
    }
}