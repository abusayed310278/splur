<?php

namespace App\Models;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'subcategory_id',
        'heading',
        'author',
        'date',
        'sub_heading',
        'body1',
        'image1',
        'image2',
        'image2_url',
        'advertising_image',
        'tags',
        'imageLink',
        'advertisingLink',
        'user_id',
        'status',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'tags' => 'array',
        'image2' => 'array',
        'image2_url' => 'array',
    ];

    // public function category()
    // {
    //     return $this->belongsTo(Category::class);
    // }

    // public function subcategory()
    // {
    //     return $this->belongsTo(SubCategory::class);
    // }

    // App/Models/Content.php

    public function likes()
    {
        return $this->hasMany(\App\Models\ContentLike::class);
    }

    public function shares()
    {
        return $this->hasMany(\App\Models\ContentShare::class);
    }

    public function isLikedBy($user): bool
    {
        if (!$user)
            return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categorys()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategorys()
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // In App\Models\Content.php
}
