<?php

namespace App\Abstracts;

use Illuminate\Support\Collection;

abstract class DataType
{
    public ?string $type = null;
    public ?string $date = null;

    abstract public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection;
}
