<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, User> $users
 */
#[Fillable(['name'])]
class Role extends Model
{
    /**
     * Raises tickets.
     */
    public const REQUESTER = 'requester';

    /**
     * Works on and resolves tickets.
     */
    public const AGENT = 'agent';

    /**
     * Manages the system.
     */
    public const ADMIN = 'admin';

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
