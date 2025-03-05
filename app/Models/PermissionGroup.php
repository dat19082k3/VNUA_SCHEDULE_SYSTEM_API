<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'description', 'parent_code'];

    public function children()
    {
        return $this->hasMany(PermissionGroup::class, 'parent_code', 'code');
    }

    public function parent()
    {
        return $this->belongsTo(PermissionGroup::class, 'parent_code', 'code');
    }
}
