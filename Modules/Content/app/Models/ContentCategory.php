<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ContentCategory extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Modules\Content\Database\Factories\ContentCategoryFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_category', 'category_id', 'news_id');
    }
}
