<?php

namespace App\Controller\Api;

use App\Gomoku\Entity\Game;
use App\Gomoku\Entity\GameMove;
use App\Gomoku\Utils\GameHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tightenco\Collect\Support\Collection;

class GameMoveController extends AbstractController
{
    /**
     * @var GameHandler
     */
    private $gameHandler;

    /**
     * MovesController constructor.
     * @param GameHandler $gameHandler
     */
    public function __construct(GameHandler $gameHandler)
    {
        $this->gameHandler = $gameHandler;
    }

    /**
     * Siirron lisääminen peliin
     *
     * @param Request $request
     * @param int $gameId
     * @return Response
     */
    public function save(Request $request, $gameId)
    {
        ['x' => $x, 'y' => $y, 'player_id' => $playerId] = $this->getGameMoveParameters($request);

        return new JsonResponse(
            $this->gameHandler->addGameMove($playerId, $x, $y, $gameId),
            Response::HTTP_CREATED
        );
    }

    /**
     * Siirron poistaminen pelistä (sallii ainoastaan viimeisimmän siirron poistamisen
     * mikäli poisto tehdään 5 sekunnin kuluttua sen tekemisestä)
     *
     * @param int $gameId
     * @param int $moveId
     * @return Response
     */
    public function delete($gameId, $moveId)
    {
        return new JsonResponse($this->gameHandler->undoGameMove($gameId, $moveId));
    }

    private function getGameMoveParameters(Request $request)
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
}
