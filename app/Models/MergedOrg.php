<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MergedOrg extends Model
{
    use HasFactory;

    protected $table = 'merged_org';

    public function district(){
        return $this->belongsTo(District::class);
    }
}
