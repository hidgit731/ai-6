<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ListNotesRequest
{
    public function __construct(
        #[Assert\Positive(message: 'Значение должно быть положительным числом.')]
        public readonly int $page = 1,
        public readonly ?string $folderId = null,
        public readonly array $tags = [],
    ) {
    }
}
