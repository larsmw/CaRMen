<?php

namespace CaRMen\Entity;

use CaRMen\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $note_value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNoteValue(): ?string
    {
        return $this->note_value;
    }

    public function setNoteValue(string $note_value): static
    {
        $this->note_value = $note_value;

        return $this;
    }
}
