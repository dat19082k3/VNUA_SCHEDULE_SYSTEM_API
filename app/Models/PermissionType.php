<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionType extends Model
{
    protected $fillable = ['code', 'name', 'position'];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'permission_type_code', 'code');
    }
}
