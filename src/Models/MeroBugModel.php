<?php

namespace MeroBug\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeroBugModel extends Model
{
    protected $fillable= ['user','project','exception','addtional'];
    protected $table="mero_bugs";
    // use HasFactory;
}