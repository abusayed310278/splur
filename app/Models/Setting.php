<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'logo',
        'profile_pic',
        'color',
        'border_color',
        'bg_color',
        'menu_item_color',
        'menu_item_active_color',
        // Footer fields
        'facebook_icon',
        'facebook_link',
        'twitter_icon',
        'twitter_link',
        'linkedin_icon',
        'linkedin_link',
        'instagram_icon',
        'instagram_link',
        'app_store_icon',
        'app_store_link',
        'google_play_icon',
        'google_play_link',
        'copyright',
        'text_color', // Added text_color field
        'active_text_color'
    ];
}
