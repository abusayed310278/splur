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
        'advertising_image',
        'tags',
        'imageLink',
        'advertisingLink',
        'user_id',
        'status',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    // public function category()
    // {
    //     return $this->belongsTo(Category::class);
    // }

    // public function subcategory()
    // {
    //     return $this->belongsTo(SubCategory::class);
    // }

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
