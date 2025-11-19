<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MahallaCluster extends Model
{
    use HasFactory;

    protected $table = 'mahalla_cluster';

    public function mahalla(){
        return $this->belongsTo(MahallasCode::class, 'stir', 'stir');
    }
}
