<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\NtlData;
use Illuminate\Support\Collection;

class Ntl extends DataType
{
    public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection
    {
        if ($activeRegion == 'republic') {
            return NtlData::with('district')
                ->where('date', $date)
                ->orderBy('ntl_mean', 'DESC')
                ->get();
        }

        return NtlData::with('district')
            ->where('date', $date)
            ->where(fn($q) => whereDistrictPrefix($q, $activeRegion))
            ->orderByRaw('ntl_mean DESC nulls last')
            ->get();
    }
}
