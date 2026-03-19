<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSlugHistory extends Model
{
    // Explicit table name — Laravel would pluralize to 'organization_slug_histories'
    protected $table = 'organization_slug_history';

    public $timestamps = true;
    const UPDATED_AT = null;   // disable updated_at only; created_at is still auto-managed

    protected $fillable = ['organization_id', 'slug', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
