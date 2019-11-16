<?php declare(strict_types=1);

namespace App\Controller\Api;

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

    public function __construct(GameHandler $gameHandler)
    {
        $this->gameHandler = $gameHandler;
    }

    public function save(Request $request, int $gameId): JsonResponse
    {
        ['x' => $x, 'y' => $y, 'player_id' => $playerId] = $this->getGameMoveParameters($request);

        return new JsonResponse(
            $this->gameHandler->addGameMove($gameId, $playerId, $x, $y),
            Response::HTTP_CREATED
        );
    }

    public function delete(int $gameId, int $moveId): JsonResponse
    {
        return new JsonResponse($this->gameHandler->undoGameMove($gameId, $moveId));
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
}
