<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Official extends Model {
    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:00',
        'updated_at' => 'datetime:Y-m-d H:00'
    ];
}
