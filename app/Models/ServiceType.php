<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ServiceType extends Model
{
    protected $fillable = ['slug', 'name', 'color', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function badgeClasses(): string
    {
        return match ($this->color) {
            'red'      => 'bg-red-100 text-red-700',
            'orange'   => 'bg-orange-100 text-orange-700',
            'amber'    => 'bg-amber-100 text-amber-700',
            'emerald'  => 'bg-emerald-100 text-emerald-700',
            'teal'     => 'bg-teal-100 text-teal-700',
            'cyan'     => 'bg-cyan-100 text-cyan-700',
            'sky'      => 'bg-sky-100 text-sky-700',
            'blue'     => 'bg-blue-100 text-blue-700',
            'indigo'   => 'bg-indigo-100 text-indigo-700',
            'violet'   => 'bg-violet-100 text-violet-700',
            'purple'   => 'bg-purple-100 text-purple-700',
            'fuchsia'  => 'bg-fuchsia-100 text-fuchsia-700',
            'pink'     => 'bg-pink-100 text-pink-700',
            'rose'     => 'bg-rose-100 text-rose-700',
            default    => 'bg-gray-100 text-gray-600',
        };
    }

    public function dotClasses(): string
    {
        return match ($this->color) {
            'red'      => 'bg-red-500',
            'orange'   => 'bg-orange-500',
            'amber'    => 'bg-amber-500',
            'emerald'  => 'bg-emerald-500',
            'teal'     => 'bg-teal-500',
            'cyan'     => 'bg-cyan-500',
            'sky'      => 'bg-sky-500',
            'blue'     => 'bg-blue-500',
            'indigo'   => 'bg-indigo-500',
            'violet'   => 'bg-violet-500',
            'purple'   => 'bg-purple-500',
            'fuchsia'  => 'bg-fuchsia-500',
            'pink'     => 'bg-pink-500',
            'rose'     => 'bg-rose-500',
            default    => 'bg-gray-500',
        };
    }

    public static function colorOptions(): Collection
    {
        return collect([
            'gray'    => 'Gray',
            'slate'   => 'Slate',
            'red'     => 'Red',
            'orange'  => 'Orange',
            'amber'   => 'Amber',
            'emerald' => 'Emerald',
            'teal'    => 'Teal',
            'cyan'    => 'Cyan',
            'sky'     => 'Sky',
            'blue'    => 'Blue',
            'indigo'  => 'Indigo',
            'violet'  => 'Violet',
            'purple'  => 'Purple',
            'fuchsia' => 'Fuchsia',
            'pink'    => 'Pink',
            'rose'    => 'Rose',
        ]);
    }
}
