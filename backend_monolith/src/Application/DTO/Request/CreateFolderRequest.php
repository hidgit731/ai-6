<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateFolderRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название не может быть пустым.')]
        #[Assert\Length(min: 1, max: 255)]
        public string $name = '',
        #[Assert\Uuid(versions: [Assert\Uuid::V4_RANDOM, Assert\Uuid::V7_MONOTONIC])]
        public ?string $parentId = null,
    ) {
    }
}
