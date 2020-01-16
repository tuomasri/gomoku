<?php declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Game;
use App\Entity\GameMove;
use App\Repository\GameRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tightenco\Collect\Support\Collection;

class GameMoveController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function save(Request $request, int $gameId): JsonResponse
    {
        ['x' => $x, 'y' => $y, 'player_id' => $playerId] = $this->getGameMoveParameters($request);

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

        return new JsonResponse($game, Response::HTTP_CREATED);
    }

    public function deleteLatest(int $gameId)
    {
        $game = $this->getGame($gameId);
        $move = $game->undoLatestGameMove();

        $this->objectManager->remove($move);

        $this->objectManager->flush();

        return new JsonResponse($game);
    }

    private function getGameMoveParameters(Request $request): array
    {
        $gameMove = json_decode($request->getContent(), true);

        $assertFunction = function ($propertyName) use ($gameMove) {
            $value = $gameMove[$propertyName] ?? null;

            if (! is_numeric($value)) {
                throw new BadRequestHttpException(sprintf(
                    '%s: was expecting numeric value in request property %s, got: %s',
                    __CLASS__,
                    $propertyName,
                    ($value !== null ? $value : "null")
                ));
            }
        };

        $mapFunction = function ($propertyName) use ($gameMove) {
            return [$propertyName => (int) $gameMove[$propertyName]];
        };

        return Collection::make(['x', 'y', 'player_id'])
            ->each($assertFunction)
            ->mapWithKeys($mapFunction)
            ->all();
    }

    private function getGame(int $gameId): Game
    {
        /** @var GameRepository $repository */
        $repository = $this->objectManager->getRepository('Gomoku:Game');

        return $repository->findGameOrFail($gameId);
    }
}
