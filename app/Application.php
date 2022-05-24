<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:00',
        'updated_at' => 'datetime:Y-m-d H:00'
    ];

    public function resident() {
        return $this->belongsTo(Resident::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
