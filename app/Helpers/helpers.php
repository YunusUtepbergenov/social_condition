
<?php

use App\Models\District;
use App\Models\Region;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

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

if (!function_exists('validateColumn')) {
    /**
     * Validate that a string is a real column name on the given table.
     * Prevents SQL injection when column names must be used in DB::raw().
     */
    function validateColumn(string $column, string $table = 'merged_org'): string
    {
        $columns = Cache::remember("schema_columns_{$table}", 3600, function () use ($table) {
            return Schema::getColumnListing($table);
        });

        if (!in_array($column, $columns, true)) {
            abort(403, 'Invalid column name.');
        }

        return $column;
    }
}

if (!function_exists('validateColumns')) {
    /**
     * Validate an array of column names against a table schema.
     */
    function validateColumns(array $columns, string $table = 'merged_org'): array
    {
        foreach ($columns as $column) {
            validateColumn($column, $table);
        }
        return $columns;
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
