<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    private ?Player $currentPlayer = null;

    #[ORM\Column]
    private string $direction = 'normal';

    #[ORM\Column]
    private string $status = 'playing';

    #[ORM\ManyToOne(targetEntity: Card::class)]
    private ?Card $topCard = null;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Player::class)]
    private Collection $players;

    public function __construct()
    {
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrentPlayer(): ?Player
    {
        return $this->currentPlayer;
    }

    public function setCurrentPlayer(?Player $currentPlayer): static
    {
        $this->currentPlayer = $currentPlayer;
        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): static
    {
        $this->direction = $direction;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getTopCard(): ?Card
    {
        return $this->topCard;
    }

    public function setTopCard(?Card $topCard): static
    {
        $this->topCard = $topCard;
        return $this;
    }

    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setGame($this);
        }
        return $this;
    }

    public function removePlayer(Player $player): static
    {
        if ($this->players->removeElement($player)) {
            if ($player->getGame() === $this) {
                $player->setGame(null);
            }
        }
        return $this;
    }
}
