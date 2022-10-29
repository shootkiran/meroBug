<?php

namespace MeroBug\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeroBugModel extends Model
{
    protected $fillable = [
        'user',
        'environment',
        'host',
        'method',
        'fullUrl',
        'exception',
        'error',
        'line',
        'file',
        'class',
        'release',
        'storage',
        'executor',
        'project_version',
        'status',

    ];
    protected $dates =['created_at'];
    protected $table = "mero_bugs";
    public function Fixes()
    {
        return $this->hasOne(MeroBugFix::class,'exception','exception')->whereFile($this->file)->whereClass($this->class);
    }
    public function getStorageObjectAttribute()
    {
        return json_decode($this->storage);
    }
    public function getUserObjectAttribute()
    {
        return json_decode($this->user);
    }
    public function getServerObjectAttribute()
    {
        return $this->storage_object->SERVER;
    }
    public function getCookieObjectAttribute()
    {
        return $this->storage_object->COOKIE;
    }
    public function getSessionObjectAttribute()
    {
        return $this->storage_object->SESSION;
    }
    public function getHeadersObjectAttribute()
    {
        return $this->storage_object->HEADERS;
    }
}
