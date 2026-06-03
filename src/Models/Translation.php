<?php

namespace Aura\Translations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    protected $table = 'aura_translations';

    protected $fillable = [
        'locale',
        'status',
        'values',
        'source_locale',
        'published_at',
    ];

    protected $casts = [
        'values' => 'array',
        'published_at' => 'datetime',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
