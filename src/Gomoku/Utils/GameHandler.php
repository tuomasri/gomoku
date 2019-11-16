<?php declare(strict_types=1);

namespace App\Gomoku\Utils;

use App\Gomoku\Entity\Game;
use App\Gomoku\Entity\GameMove;
use App\Repository\GameRepository;
use Doctrine\Common\Persistence\ObjectManager;

class GameHandler
{
    private const DEFAULT_BOARD_SIZE = 15;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * GameHandler constructor.
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function handleGameStart(int $boardSize = null): Game
    {
        $game = Game::initializeGame($boardSize ?: self::DEFAULT_BOARD_SIZE);

        $this->objectManager->persist($game);

        $this->objectManager->flush();

        return $game;
    }

    public function addGameMove(int $gameId, int $playerId, int $x, int $y): Game
    {
        $game = $this->getGame($gameId);

        $gameMove = new GameMove($game, $game->getPlayerById($playerId), $x, $y);

        /**
         * Siirtojen hanskaaminen ei oo maailman nätein ratkaisu mutta toimii:
         * koska naapurit linkataan json-muotoiseen taulukkoon, pitää naapurisiirron ID olla
         * tiedossa ennen linkkaamista (jonka takia tallennus joudutaan tekemään kahdessa vaiheessa).
         * Toimii näinkin, mutta voisi olla parempi linkata naapurit erillisten kenttien kautta.
         */
        $this->objectManager->transactional(
            function (ObjectManager $objectManager) use ($game, $gameMove) {
                $iterator = $game->handleNewGameMove($gameMove);

                foreach ($iterator as $_) {
                    $objectManager->flush();
                }
            }
        );

        return $game;
    }

    public function undoGameMove(int $gameId, int $moveId): Game
    {
        $game = $this->getGame($gameId);
        $move = $game->undoGameMove((int) $moveId);

        $this->objectManager->remove($move);

        $this->objectManager->flush();

        return $game;
    }

    private function getGame(int $gameId): Game
    {
        /** @var GameRepository $repository */
        $repository = $this->objectManager->getRepository('Gomoku:Game');

        return $repository->findGameOrFail($gameId);
    }
}
