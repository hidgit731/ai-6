<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Trait\TimestampsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'tag')]
#[ORM\UniqueConstraint(name: 'uq_tag_name', columns: ['name'])]
class Tag
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: Note::class, mappedBy: 'tags')]
    private Collection $notes;

    public function __construct(string $name)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->notes = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }
}
