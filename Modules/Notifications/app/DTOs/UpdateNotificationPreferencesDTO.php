<?php

namespace Modules\Notifications\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class UpdateNotificationPreferencesDTO extends Data
{
    public function __construct(
        #[Required]
        #[DataCollectionOf(NotificationPreferenceItemDTO::class)]
        public DataCollection $preferences,
    ) {}

    public function toArray(): array
    {
        return [
            'preferences' => $this->preferences->toArray(),
        ];
    }
}
