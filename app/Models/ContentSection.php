<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSection extends Model
{
    public const PAGE_PAYSCALE = 'payscale';
    public const PAGE_PAYMENT_POLICY = 'payment_policy';
    public const PAGE_POST_COVERAGE_SCHEDULE = 'post_coverage_schedule';

    protected $fillable = ['page_slug', 'title', 'body', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public static function pageSlugOptions(): array
    {
        return [
            self::PAGE_PAYSCALE => 'Pay Scale',
            self::PAGE_PAYMENT_POLICY => 'Payment Model & Policy',
            self::PAGE_POST_COVERAGE_SCHEDULE => 'Post Coverage Schedule',
        ];
    }

    public function scopeForPage($query, string $slug)
    {
        return $query->where('page_slug', $slug);
    }

    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('order')->orderBy('id');
    }
}
