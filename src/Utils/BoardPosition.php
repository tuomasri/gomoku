<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 7.6.2017
 * Time: 18:40
 */

namespace App\Utils;


use App\Entity\GameMove;

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

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public static function advanceOneStep(GameMove $gameMove, BoardDirection $direction): BoardPosition
    {
        $newCoordinates = self::getCoordinatesInDirection($gameMove, $direction);

        return new self(...$newCoordinates);
    }

    private static function getCoordinatesInDirection(GameMove $gameMove, BoardDirection $direction): array
    {
        [$x, $y] = $gameMove->getPosition();

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