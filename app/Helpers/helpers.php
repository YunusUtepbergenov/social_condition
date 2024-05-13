
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

if (!function_exists('numberToWords')) {
    function numberToWords($number) {
        $isNegative = false;
        if ($number < 0) {
            $isNegative = true;
            $number = abs($number);
        }

        if ($number >= 1000000000) {
            $result = number_format($number / 1000000000, 1) . ' млрд.';
        } elseif ($number >= 1000000) {
            $result = number_format($number / 1000000, 1) . ' млн.';
        } elseif ($number >= 1000) {
            $result = number_format($number / 1000, 1) . ' минг';
        } else {
            $result = number_format(round($number, 1 ), 1, '.', ' ');
        }

        if ($isNegative) {
            $result = '-' . $result;
        }

        return $result;
    }
}
