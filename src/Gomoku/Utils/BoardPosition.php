<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 7.6.2017
 * Time: 18:40
 */

namespace App\Gomoku\Utils;


use App\Gomoku\Entity\GameMove;

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
     * BoardPosition constructor.
     * @param int $x
     * @param int $y
     */
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @param GameMove $gameMove
     * @param BoardDirection $direction
     * @return BoardPosition
     */
    public static function advanceOneStep(GameMove $gameMove, BoardDirection $direction)
    {
        $newCoordinates = self::getCoordinatesInDirection($gameMove, $direction);

        return new self(...$newCoordinates);
    }

    /**
     * @param GameMove $gameMove
     * @param BoardDirection $direction
     * @return int[]
     */
    private static function getCoordinatesInDirection(GameMove $gameMove, BoardDirection $direction)
    {
        $x = $gameMove->getX();
        $y = $gameMove->getY();

        switch ($direction->getDirectionName()) {
            case BoardDirection::DIRECTION_NORTH:
                return [$x, $y - 1];

            case BoardDirection::DIRECTION_SOUTH:
                return [$x, $y + 1];

            case BoardDirection::DIRECTION_WEST:
                return [$x - 1, $y];

            case BoardDirection::DIRECTION_EAST:
                return [$x + 1, $y];

            case BoardDirection::DIRECTION_NORTHEAST:
                return [$x + 1, $y - 1];

            case BoardDirection::DIRECTION_SOUTHEAST:
                return [$x + 1, $y + 1];

            case BoardDirection::DIRECTION_SOUTHWEST:
                return [$x - 1, $y + 1];

            case BoardDirection::DIRECTION_NORTHWEST:
                return [$x - 1, $y - 1];
        }
    }

}