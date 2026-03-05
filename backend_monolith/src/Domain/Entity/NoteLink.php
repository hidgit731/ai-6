<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'note_link')]
#[ORM\UniqueConstraint(name: 'uq_note_link_source_target', columns: ['source_note_id', 'target_note_id'])]
#[ORM\Index(columns: ['source_note_id'], name: 'idx_note_link_source_note_id')]
#[ORM\Index(columns: ['target_note_id'], name: 'idx_note_link_target_note_id')]
class NoteLink
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Note::class)]
    #[ORM\JoinColumn(name: 'source_note_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private Note $sourceNote;

    #[ORM\ManyToOne(targetEntity: Note::class)]
    #[ORM\JoinColumn(name: 'target_note_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private Note $targetNote;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Note $sourceNote, Note $targetNote)
    {
        $this->id = Uuid::v7();
        $this->sourceNote = $sourceNote;
        $this->targetNote = $targetNote;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSourceNote(): Note
    {
        return $this->sourceNote;
    }

    public function getTargetNote(): Note
    {
        return $this->targetNote;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
