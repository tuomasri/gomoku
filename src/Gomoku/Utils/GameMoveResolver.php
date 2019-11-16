<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.4.2018
 * Time: 19:54
 */

namespace App\Gomoku\Utils;

use App\Gomoku\Entity\Game;
use App\Gomoku\Entity\GameMove;
use Tightenco\Collect\Support\Collection;

class GameMoveResolver
{
    public function getSurroundingNeighbourMoves(GameMove $latestGameMove): Collection
    {
        return Collection::make(BoardDirection::getDirections())
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

    public function getWinningGameMoves(GameMove $newestGameMove): Collection
    {
        return BoardDirection::asOppositePairs()->reduce(
            function (Collection $gameMoves, array $directionPairs) use ($newestGameMove) {
                // Voittosiirtojen sarja on jo löytynyt
                if ($gameMoves->count() >= Game::WINNING_NUM_OF_MOVES) {
                    return $gameMoves;
                }

                [$direction, $oppositeDirection] = $directionPairs;

                /**
                 * Tiedetään, että voitto on tapahtunut jos siirrosta kumpaankin vastakkaiseen
                 * suuntaan lähdettäessä löydetään siirrolle vähintään 4 naapurisiirtoa
                 */
                $gameMoves = $newestGameMove->getNeighbourMovesInDirection($direction);
                $gameMovesOppositeDirection = $newestGameMove->getNeighbourMovesInDirection($oppositeDirection);

                return Collection::make()
                    ->merge($gameMoves)
                    ->merge($gameMovesOppositeDirection)
                    ->push($newestGameMove)
                    ->take(Game::WINNING_NUM_OF_MOVES)
                    ->pipe(
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
