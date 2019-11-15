<?php
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

    /**
     * Player constructor.
     * @param Game $game
     * @param int $color
     */
    private function __construct(Game $game, $color)
    {
        $this->game = $game;
        $this->color = $color;
    }

    /**
     * @param Game $game
     * @return Player
     */
    public static function createBlackPlayer(Game $game)
    {
        return new self($game, self::COLOR_BLACK);
    }

    /**
     * @param Game $game
     * @return Player
     */
    public static function createWhitePlayer(Game $game)
    {
        return new self($game, self::COLOR_WHITE);
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return bool
     */
    public function matchesId($id)
    {
        return $this->id === $id;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id'    => $this->id,
            'color' => $this->color,
        ];
    }
}