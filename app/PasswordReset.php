<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model {
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'date:Y-m-d',
    ];
}
