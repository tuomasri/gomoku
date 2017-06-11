<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 7.6.2017
 * Time: 18:40
 */

namespace App\Gomoku\Utils;


use App\Gomoku\Model\Game;
use App\Gomoku\Model\GameMove;

/**
 * Helpperityylinen luokka pelitilanteen resolvointiin
 *
 * Class BoardPosition
 * @package App\Gomoku\Utils
 */
class BoardPosition
{
    /**
     * @var int
     */
    public $x;

    /**
     * @var int
     */
    public $y;

    /**
     * @var array
     */
    const DIRECTION_FACTORY_METHODS = [
        GameMove::DIRECTION_NORTH     => 'north',
        GameMove::DIRECTION_NORTHEAST => 'northeast',
        GameMove::DIRECTION_EAST      => 'east',
        GameMove::DIRECTION_SOUTHEAST => 'southeast',
        GameMove::DIRECTION_SOUTH     => 'south',
        GameMove::DIRECTION_SOUTHWEST => 'southwest',
        GameMove::DIRECTION_WEST      => 'west',
        GameMove::DIRECTION_NORTHWEST => 'northwest',
    ];

    /**
     * @var array
     */
    const DIRECTION_OPPOSITES = [
        GameMove::DIRECTION_NORTH     => GameMove::DIRECTION_SOUTH,
        GameMove::DIRECTION_NORTHEAST => GameMove::DIRECTION_SOUTHWEST,
        GameMove::DIRECTION_EAST      => GameMove::DIRECTION_WEST,
        GameMove::DIRECTION_SOUTHEAST => GameMove::DIRECTION_NORTHWEST,
        GameMove::DIRECTION_SOUTH     => GameMove::DIRECTION_NORTH,
        GameMove::DIRECTION_SOUTHWEST => GameMove::DIRECTION_NORTHEAST,
        GameMove::DIRECTION_WEST      => GameMove::DIRECTION_EAST,
        GameMove::DIRECTION_NORTHWEST => GameMove::DIRECTION_SOUTHEAST,
    ];

    /**
     * Direction constructor.
     * @param int $x
     * @param int $y
     */
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return BoardPosition|null
     */
    public function returnInstanceIfWithinGameBoard()
    {
        $isWithinBounds =
            $this->x >= 0 && $this->x < Game::BOARD_SIZE &&
            $this->y >= 0 && $this->y < Game::BOARD_SIZE;

        return $isWithinBounds ? $this : null;
    }

    /**
     * @param int $direction
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function getOppositeDirection($direction)
    {
        $opposite = self::DIRECTION_OPPOSITES[$direction] ?? null;

        if (! $opposite) {
            throw new \InvalidArgumentException(
                __CLASS__ . ": unknown direction: {$direction}"
            );
        }

        return $opposite;
    }

    /**
     * Luo annetusta siirrosta ja suunnasta pelilaudan paikkaa mallintavan instanssin.
     * Jos $offset = 0 niin ei siirry $gameMoven koordinaateista mihinkään.
     *
     * @param GameMove $gameMove
     * @param int $direction
     * @param int $offset
     * @return BoardPosition|null
     * @throws \InvalidArgumentException
     */
    public static function createFromDirection(GameMove $gameMove, $direction, $offset)
    {
        $factoryMethod = self::DIRECTION_FACTORY_METHODS[$direction] ?? null;

        if (! $factoryMethod) {
            throw new \InvalidArgumentException(
                __CLASS__ . ": factory method for direction {$direction} was not found"
            );
        }

        return self::$factoryMethod($gameMove, $offset);
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function north(GameMove $gameMove, $offset)
    {
        return (
            new self(
                $gameMove->getX(),
                $gameMove->getY() - $offset
            )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function south(GameMove $gameMove, $offset)
    {
        return (
        new self(
            $gameMove->getX(),
            $gameMove->getY() + $offset
        )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function west(GameMove $gameMove, $offset)
    {
        return (
        new self(
            $gameMove->getX() - $offset,
            $gameMove->getY()
        )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function east(GameMove $gameMove, $offset)
    {
        return (
        new self(
            $gameMove->getX() + $offset,
            $gameMove->getY()
        )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function northeast(GameMove $gameMove, $offset)
    {
        return (
            new self(
                $gameMove->getX() + $offset,
                $gameMove->getY() - $offset
            )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function southeast(GameMove $gameMove, $offset)
    {
        return (
            new self(
                $gameMove->getX() + $offset,
                $gameMove->getY() + $offset
            )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition
     */
    private static function southwest(GameMove $gameMove, $offset)
    {
        return (
            new self(
                $gameMove->getX() - $offset,
                $gameMove->getY() + $offset
            )
        )->returnInstanceIfWithinGameBoard();
    }

    /**
     * @param GameMove $gameMove
     * @param int $offset
     * @return BoardPosition|null
     */
    private static function northwest(GameMove $gameMove, $offset)
    {
        return (
            new self(
                $gameMove->getX() - $offset,
                $gameMove->getY() - $offset
            )
        )->returnInstanceIfWithinGameBoard();
    }
}