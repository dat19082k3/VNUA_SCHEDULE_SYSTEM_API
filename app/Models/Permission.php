<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['code', 'name', 'description', 'permission_group_code', 'permission_type_code'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_roles');
    }

    public function group()
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_code', 'code');
    }

    public function type()
    {
        return $this->belongsTo(PermissionType::class, 'permission_type_code', 'code');
    }
}
