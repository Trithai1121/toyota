<?php

namespace App\Models;

use App\Services\PostContentSanitizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title', 'slug', 'excerpt', 'source_url', 'cover_image_path', 'image_credit', 'cover_image_alt', 'body', 'seo_title', 'seo_description',
    'status', 'published_at',
])]
class Post extends Model
{
    public function scopeReadyToPublish(Builder $query): Builder
    {
        return $query
            ->whereNotNull('cover_image_path')->where('cover_image_path', '!=', '')
            ->whereNotNull('cover_image_alt')->where('cover_image_alt', '!=', '')
            ->whereNotNull('image_credit')->where('image_credit', '!=', '')
            ->whereNotNull('source_url')->where('source_url', '!=', '');
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            $post->body = app(PostContentSanitizer::class)->clean($post->body);
        });
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
