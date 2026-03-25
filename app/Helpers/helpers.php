
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

if (!function_exists('whereDistrictPrefix')) {
    /**
     * Add a range-based prefix filter for bigint district_code columns.
     * Replaces LIKE '{prefix}%' which requires text cast and prevents index usage.
     */
    function whereDistrictPrefix($query, string $prefix, string $column = 'district_code')
    {
        $len = strlen($prefix);
        $min = intval($prefix) * pow(10, 7 - $len);
        $max = (intval($prefix) + 1) * pow(10, 7 - $len);
        return $query->where($column, '>=', $min)->where($column, '<', $max);
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
