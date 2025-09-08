<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name','slug'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'subcategory_id');
    }
}
