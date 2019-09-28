<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteGroup extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }
}
