<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function scopeFilterByIlikeName(Builder $query, ?string $name): void
    {
        $query->when($name, function ($query) use ($name) {
            $query->orWhere('name', 'ILIKE', '%'.$name.'%');
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id')->select(['id', 'name']);
    }
}
