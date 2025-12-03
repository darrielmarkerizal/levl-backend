<?php

namespace Modules\Forums\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    use HasFactory;

    /**
     * Reaction types.
     */
    const TYPE_LIKE = 'like';

    const TYPE_HELPFUL = 'helpful';

    const TYPE_SOLVED = 'solved';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'reactable_type',
        'reactable_id',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who created the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Get the parent reactable model (thread or reply).
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Toggle a reaction for a user on a specific content.
     * If the reaction exists, it will be removed. Otherwise, it will be created.
     *
     * @return bool True if reaction was added, false if removed
     */
    public static function toggle(int $userId, string $reactableType, int $reactableId, string $type): bool
    {
        $reaction = static::where([
            'user_id' => $userId,
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
            'type' => $type,
        ])->first();

        if ($reaction) {
            $reaction->delete();

            return false;
        }

        static::create([
            'user_id' => $userId,
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
            'type' => $type,
        ]);

        return true;
    }

    /**
     * Get all available reaction types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_LIKE,
            self::TYPE_HELPFUL,
            self::TYPE_SOLVED,
        ];
    }

    /**
     * Check if a type is valid.
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getTypes());
    }
}
