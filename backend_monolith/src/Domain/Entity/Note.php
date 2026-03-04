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
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'note')]
#[ORM\Index(columns: ['created_at'], name: 'idx_note_created_at')]
class Note
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Folder $folder = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'notes')]
    #[ORM\JoinTable(name: 'note_tag')]
    private Collection $tags;

    public function __construct(string $title, ?string $content = null)
    {
        $this->id = Uuid::v7();
        $this->title = $title;
        $this->content = $content;
        $this->tags = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): void
    {
        $this->folder = $folder;
    }

    public function getFolderId(): ?string
    {
        return $this->folder?->getId()->toRfc4122();
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function clearTags(): void
    {
        $this->tags->clear();
    }

    public function setTags(Collection $tags): void
    {
        $this->tags = $tags;
    }
}
