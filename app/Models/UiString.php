<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'description'])]
class UiString extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('website.ui-strings.v1'));
        static::deleted(fn () => Cache::forget('website.ui-strings.v1'));
    }
}
