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
     * @param int $color
     * @throws \InvalidArgumentException
     */
    public function __construct($color = self::COLOR_BLACK)
    {
        if (! ($color === self::COLOR_BLACK || $color === self::COLOR_WHITE)) {
            throw new \InvalidArgumentException(
                __CLASS__ . ": was not expecting {$color} as color"
            );
        }

        $this->color = $color;
    }

    /**
     * @return Player
     */
    public static function createBlackPlayer()
    {
        return new self(self::COLOR_BLACK);
    }

    /**
     * @return Player
     */
    public static function createWhitePlayer()
    {
        return new self(self::COLOR_WHITE);
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
    public function hasId($id)
    {
        return $this->id === $id;
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame(Game $game)
    {
        $this->game = $game;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isSameColor(Player $player)
    {
        return $this->color === $player->getColor();
    }

    /**
     * @return bool
     */
    public function isBlack()
    {
        return $this->color === self::COLOR_BLACK;
    }

    /**
     * @return bool
     */
    public function isWhite()
    {
        return ! $this->isBlack();
    }

    /**
     * @return int
     */
    public function getColor()
    {
        return $this->color;
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