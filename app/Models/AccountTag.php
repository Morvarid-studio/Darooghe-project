<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccountTag extends Model
{
    protected $table = 'account_tags';

    protected $fillable = [
        'name',
        'color',
    ];

    // رابطه many-to-many با accounts
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_tag', 'tag_id', 'account_id');
    }
}

