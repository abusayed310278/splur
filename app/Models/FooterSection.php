<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterSection extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'pages'];

    protected $casts = [
        'pages' => 'array',
    ];
    
}
