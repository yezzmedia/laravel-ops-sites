<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsSiteDomain extends Model
{
    protected $table = 'ops_site_domains';

    protected $guarded = [];

    /**
     * @return BelongsTo<OpsSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(OpsSite::class, 'site_id');
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'bool',
            'expected_dns_targets' => 'array',
            'resolved_dns_targets' => 'array',
        ];
    }
}
