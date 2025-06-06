<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    // Define fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'comment',
    ];

    /**
     * Relationship: A comment belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
