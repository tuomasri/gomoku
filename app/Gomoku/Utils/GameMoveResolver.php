<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.4.2018
 * Time: 19:54
 */

namespace App\Gomoku\Utils;

use App\Gomoku\Model\Game;
use App\Gomoku\Model\GameMove;
use Illuminate\Support\Collection;

class GameMoveResolver
{
    /**
     * Palauttaa viimeisimmän tehdyn siirron naapurisiirrot (siirto on naapurisiirto jos se
     * on 1 askeleen päässä viimeisimmästä siirrosta ja saman pelaajan tekemä)
     *
     * @param GameMove $latestGameMove
     * @return Collection<NeighbourMoveDTO>
     */
    public function getNeighbourMoves(GameMove $latestGameMove)
    {
        return
            Collection::make(BoardDirection::getDirections())
                ->reduce(
                    function (Collection $neighbourMoves, BoardDirection $direction) use ($latestGameMove) {
                        $newPosition = BoardPosition::advanceOneStep($latestGameMove, $direction);

                        $neighbourMove = $latestGameMove
                            ->getGame()
                            ->getMoveInPosition(
                                $newPosition->x,
                                $newPosition->y,
                                $latestGameMove->getPlayer()
                            );

                        return $neighbourMove
                            ? $neighbourMoves->push(new NeighbourMoveDTO($neighbourMove, $direction))
                            : $neighbourMoves;
                    },
                    new Collection()
                );
    }

    /**
     * @param GameMove $newestGameMove
     * @return Collection
     */
    public function getWinningGameMoves(GameMove $newestGameMove)
    {
        return BoardDirection::asOppositePairs()
            ->reduce(
                function (Collection $gameMoves, Collection $directionPairs) use ($newestGameMove) {
                    // Voittosiirtojen sarja on jo löytynyt
                    if ($gameMoves->count() >= Game::WINNING_NUM_OF_MOVES) {
                        return $gameMoves;
                    }

                    $direction = $directionPairs->get(0);
                    $oppositeDirection = $directionPairs->get(1);

                    /**
                     * Tiedetään, että voitto on tapahtunut jos siirrosta kumpaankin vastakkaiseen
                     * suuntaan lähdettäessä löydetään siirrolle vähintään 4 naapurisiirtoa
                     */
                    $gameMoves = $newestGameMove->getNeighboursInDirection($direction);
                    $gameMovesOppositeDirection = $newestGameMove->getNeighboursInDirection($oppositeDirection);

                    return $gameMoves
                        ->merge($gameMovesOppositeDirection)
                        ->push($newestGameMove)
                        ->take(Game::WINNING_NUM_OF_MOVES)
                        ->when(
                            true,
                            function (Collection $moves) {
                                return $moves->count() >= Game::WINNING_NUM_OF_MOVES
                                    ? $moves
                                    : Collection::make();
                            }
                        );
                },
                Collection::make()
            );
    }
}