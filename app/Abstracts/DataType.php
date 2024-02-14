<?php

namespace App\Abstracts;

abstract class DataType{
    public $type;
    public $date;
    // public abstract function calculateAverage();
    public abstract function getTopDistricts($activeRegion, $activeIndicator, $date);
}