<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class MoveFolderRequest
{
    public function __construct(
        #[Assert\Uuid(versions: [Assert\Uuid::V4_RANDOM, Assert\Uuid::V7_MONOTONIC])]
        public ?string $targetParentId = null,
    ) {
    }
}
