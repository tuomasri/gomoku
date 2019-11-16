<?php

namespace App\Gomoku\Utils;

use App\Gomoku\Entity\Game;
use App\Gomoku\Entity\GameMove;
use App\Repository\GameRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NoResultException;

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

    /**
     * @param int $boardSize
     * @return Game
     */
    public function handleGameStart($boardSize = null)
    {
        $game = Game::initializeGame($boardSize ?: self::DEFAULT_BOARD_SIZE);

        $this->objectManager->persist($game);

        $this->objectManager->flush();

        return $game;
    }

    /**
     * Siirron lisääminen peliin
     *
     * @param $playerId
     * @param $x
     * @param $xy
     * @param int $gameId
     * @return Game
     * @throws NoResultException
     */
    public function addGameMove($playerId, $x, $y, $gameId)
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

    /**
     * Siirron peruminen (onnistuu vain viimeisimmälle siirrolle 5 sekunnin sisällä sen tekoajasta)
     *
     * @param int $gameId
     * @param int $moveId
     * @return Game
     * @throws \DomainException, \RuntimeException
     */
    public function undoGameMove($gameId, $moveId)
    {
        $game = $this->getGame($gameId);
        $move = $game->undoGameMove((int) $moveId);

        $this->objectManager->remove($move);

        $this->objectManager->flush();

        return $game;
    }

    /**
     * @param int $gameId
     * @return Game
     * @throws NoResultException
     */
    private function getGame($gameId)
    {
        /** @var GameRepository $repository */
        $repository = $this->objectManager->getRepository('Gomoku:Game');

        return $repository->findGameOrFail($gameId);
    }
}
