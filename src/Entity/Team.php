<?php

namespace App\Entity;

class Team
{
    private string $name;
    private string $place;
    private string $country;
    private string $logo;
    /**
     * @var TypePlayer[]
     */
    private array $players;
    private string $coach;
    private int $goals;

    public function __construct(string $name, string $country, string $logo, array $players, string $coach, string $place)
    {
        $this->assertCorrectPlayers($players);

        $this->name = $name;
        $this->place = $place; //TODO: validation value
        $this->country = $country;
        $this->logo = $logo;
        $this->players = $players;
        $this->coach = $coach;
        $this->goals = 0;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * @return TypePlayer[]
     */
    public function getPlayersOnField(): array
    {
        return array_filter($this->players, function (Player $player) {
            return $player->isPlay();
        });
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getPlayer(int $number): Player
    {
        foreach ($this->players as $player) {
            if ($player->getNumber() === $number) {
                return $player;
            }
        }

        throw new \Exception(
            sprintf(
                'Player with number "%d" not play in team "%s".',
                $number,
                $this->name
            )
        );
    }

    public function getCoach(): string
    {
        return $this->coach;
    }

    public function addGoal(): void
    {
        $this->goals += 1;
    }

    public function getGoals(): int
    {
        return $this->goals;
    }

    private function assertCorrectPlayers(array $players)
    {
        foreach ($players as $player) {
            if (!($player instanceof Player)) {
                throw new \Exception(
                    sprintf(
                        'Player should be instance of "%s". "%s" given.',
                        TypePlayer::class,
                        get_class($player)
                    )
                );
            }
        }
    }
}