<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Guarded(['id'])]
class Role extends Model
{
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
