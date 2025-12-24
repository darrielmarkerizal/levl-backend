<?php

namespace Modules\Notifications\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class SendNotificationDTO extends Data
{
    public function __construct(
        #[Required]
        #[MapInputName('user_id')]
        public int $userId,

        #[Required, Max(255)]
        public string $type,

        #[Required, Max(255)]
        public string $title,

        #[Required]
        public string $message,

        public ?array $data = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
