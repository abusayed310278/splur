<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Footer extends Model
{
    use HasFactory;
    protected $fillable = [
        'facebook_icon',
        'facebook_link',
        'instagram_icon',
        'instagram_link',
        'linkedin_icon',
        'linkedin_link',
        'app_store',
        'google_play',
        'bg_color',
        'copy_rights',
        'menus',
    ];

    protected $casts = [
        'menus' => 'array',
    ];
}
