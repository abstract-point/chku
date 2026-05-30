<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class NextAction
{
    public function __construct(
        public string $type,
        public int $priority,
        public string $title,
        public string $description,
        public string $actionUrl,
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'priority' => $this->priority,
            'title' => $this->title,
            'description' => $this->description,
            'actionUrl' => $this->actionUrl,
        ];
    }
}
