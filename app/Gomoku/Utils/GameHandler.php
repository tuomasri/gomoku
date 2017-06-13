<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 8.6.2017
 * Time: 16:33
 */

namespace App\Gomoku\Utils;

use App\Gomoku\Model\Game;
use App\Gomoku\Model\GameMove;
use App\Gomoku\Repository\GameRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Illuminate\Http\Request;

class GameHandler
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * GameHandler constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Pelin aloitus
     *
     * @return Game
     */
    public function handleGameStart()
    {
        $game = Game::startGame();

        $this->entityManager->persist($game);

        $this->entityManager->flush();

        return $game;
    }

    /**
     * Siirron lisääminen peliin
     *
     * @param Request $request
     * @param int $gameId
     * @return Game
     * @throws \InvalidArgumentException, \LogicException, NoResultException
     */
    public function addGameMove(Request $request, $gameId)
    {
        $game = $this->getGame($gameId);

        $gameMove = $this->createMoveFromRequest($game, $request);

        /**
         * Siirtojen hanskaaminen ei oo maailman nätein ratkaisu mutta toimii:
         * koska naapurit linkataan json-muotoiseen taulukkoon, pitää naapurisiirron ID olla
         * tiedossa ennen linkkaamista (jonka takia tallennus joudutaan tekemään kahdessa vaiheessa).
         * Toimii näinkin, mutta voisi olla parempi linkata naapurit erillisten kenttien kautta.
         */
        $this->entityManager->transactional(
            function (EntityManager $entityManager) use ($game, $gameMove) {
                // Siirto talteen mutta ei vielä muuta
                $game->addMove($gameMove);

                $entityManager->flush();

                // Lisätyn siirron naapurien ja mahd. voittajan resolvointi
                $game->resolveLastMoveLinksAndGameState();

                $entityManager->flush();
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

        $this->entityManager->remove($move);

        $this->entityManager->flush();

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
        $repository = $this->entityManager->getRepository('Gomoku:Game');

        return $repository->findGameOrFail($gameId);
    }

    /**
     * @param Game $game
     * @param Request $request
     * @return GameMove
     */
    private function createMoveFromRequest(Game $game, Request $request)
    {
        return new GameMove(
            $game,
            $game->getPlayerById($request->request->getInt('player_id')),
            $request->request->getInt('x'),
            $request->request->getInt('y')
        );
    }
}