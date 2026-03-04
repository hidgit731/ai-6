<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class SuggestTagsRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Поисковый запрос не может быть пустым.')]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $q = '',
    ) {
    }
}
