<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.6.2017
 * Time: 18:05
 */

namespace App\Gomoku\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="player")
 *
 * Class Player
 * @package App\Gomoku\Model
 */
class Player implements \JsonSerializable
{
    const COLOR_BLACK = 1;
    const COLOR_WHITE = 2;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     *
     * @var int
     */
    private $color;

    /**
     * @ORM\ManyToOne(targetEntity="Game", inversedBy="players")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id")
     *
     * @var Game
     */
    private $game;

    private function __construct(Game $game, int $color)
    {
        $this->game = $game;
        $this->color = $color;
    }

    public static function createBlackPlayer(Game $game): Player
    {
        return new self($game, self::COLOR_BLACK);
    }

    public static function createWhitePlayer(Game $game): Player
    {
        return new self($game, self::COLOR_WHITE);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function matchesId(int $id): bool
    {
        return $this->id === $id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'    => $this->id,
            'color' => $this->color,
        ];
    }
}
