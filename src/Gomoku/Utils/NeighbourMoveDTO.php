<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.4.2018
 * Time: 20:01
 */

namespace App\Gomoku\Utils;


use App\Gomoku\Entity\GameMove;

class NeighbourMoveDTO
{
    /**
     * @var GameMove
     */
    public $gameMove;

    /**
     * @var BoardDirection
     */
    public $boardDirection;

    public function __construct(GameMove $gameMove, BoardDirection $boardDirection)
    {
        $this->gameMove = $gameMove;
        $this->boardDirection = $boardDirection;
    }
}