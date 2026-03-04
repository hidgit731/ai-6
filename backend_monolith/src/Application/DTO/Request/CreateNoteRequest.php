<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateNoteRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Заголовок не может быть пустым.')]
        #[Assert\Length(max: 255)]
        public readonly string $title = '',
        public readonly ?string $content = null,
        #[Assert\Uuid(versions: [Assert\Uuid::V4_RANDOM, Assert\Uuid::V7_MONOTONIC])]
        public readonly ?string $folderId = null,
        public readonly array $tags = [],
    ) {
    }
}
