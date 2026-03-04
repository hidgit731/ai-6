<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTagRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название не может быть пустым.')]
        #[Assert\Length(max: 50)]
        public readonly string $name = '',
    ) {
    }
}
