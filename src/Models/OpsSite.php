<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpsSite extends Model
{
    protected $table = 'ops_sites';

    protected $guarded = [];

    /**
     * @return HasMany<OpsSiteDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(OpsSiteDomain::class, 'site_id');
    }

    /**
     * @return HasMany<OpsSiteAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(OpsSiteAssignment::class, 'site_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
