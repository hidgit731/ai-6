<?php

declare(strict_types=1);

namespace App\Application\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateFolderRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название не может быть пустым.')]
        #[Assert\Length(min: 1, max: 255)]
        public string $name = '',
    ) {
    }
}
