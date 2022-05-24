<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model {
    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:00',
        'updated_at' => 'datetime:Y-m-d H:00'
    ];

    // public function setContentAttribute($value) {
    //     $this->attributes['content'] = strip_tags($value);
    // }

    public function getTypeAttribute($value) {
        $type = $value == 1 ? 'Event' : 'Announcement';
        return $type;
    }
}
