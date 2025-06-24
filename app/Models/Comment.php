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
        'content_id',
        'comment',
    ];

    /**
     * Relationship: A comment belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function votes()
    {
        return $this->hasMany(CommentVote::class);
    }

    public function upvotesCount()
    {
        return $this->votes()->where('vote', 1)->count();
    }

    public function downvotesCount()
    {
        return $this->votes()->where('vote', -1)->count();
    }

    public function voteByUser($userId)
    {
        return $this->votes()->where('user_id', $userId)->value('vote');
    }

    
}
