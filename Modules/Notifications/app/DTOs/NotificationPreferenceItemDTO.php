<?php

namespace Modules\Notifications\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class NotificationPreferenceItemDTO extends Data
{
    public function __construct(
        #[Required]
        public string $category,

        #[Required]
        public string $channel,

        public bool $enabled = true,

        #[In(['immediate', 'daily', 'weekly'])]
        public string $frequency = 'immediate',
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'channel' => $this->channel,
            'enabled' => $this->enabled,
            'frequency' => $this->frequency,
        ];
    }
}
