<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSlugHistory extends Model
{
    protected $table = 'organization_slug_history';

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['organization_id', 'slug'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
