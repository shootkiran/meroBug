<?php

namespace MeroBug\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeroBugFix extends Model
{
    protected $fillable = [
        'exception',
        'status',
        'file',
        'class',
    ];
    protected $table = "mero_bugs_fixes";
    // use HasFactory;
}
