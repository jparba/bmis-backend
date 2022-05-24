<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resident extends Model {

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:00',
        'updated_at' => 'datetime:Y-m-d H:00'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
