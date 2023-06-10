<?php

namespace Thans\Bpm\Models;

use Illuminate\Database\Eloquent\Model;

class DataRole extends Model
{
    protected $table = 'data_roles';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $fillable = [
        'role_id',
        'form_id',
        'fields',
        'range',
    ];
}
