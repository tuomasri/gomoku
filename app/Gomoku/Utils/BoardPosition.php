<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 7.6.2017
 * Time: 18:40
 */

namespace App\Gomoku\Utils;


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
        $newCoordinates = $direction->advanceCoordinatesToDirection($gameMove->getX(), $gameMove->getY());

        return new self($newCoordinates[0], $newCoordinates[1]);
    }
}