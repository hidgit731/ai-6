<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ListNoteVersionsRequest
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        #[Assert\Range(min: 1, max: 50)]
        public readonly int $limit = 20,
    ) {
    }
}
