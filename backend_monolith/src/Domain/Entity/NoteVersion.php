<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'note_version')]
class NoteVersion
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Note::class)]
    #[ORM\JoinColumn(name: 'note_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private Note $note;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content;

    #[ORM\Column(name: 'version_number', type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Note $note, string $title, ?string $content, int $versionNumber)
    {
        $this->id = Uuid::v7();
        $this->note = $note;
        $this->title = $title;
        $this->content = $content;
        $this->versionNumber = $versionNumber;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getNote(): Note
    {
        return $this->note;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
