<?php

namespace Modules\Notifications\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreateNotificationDTO extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $type,

        #[Required, Max(255)]
        public string $title,

        #[Required]
        public string $message,

        #[MapInputName('user_id')]
        public ?int $userId = null,

        public ?array $data = null,

        public ?string $channel = null,
    ) {}

    public function toModelArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'channel' => $this->channel,
        ];
    }
}
