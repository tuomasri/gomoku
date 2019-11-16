<?php declare(strict_types=1);

namespace App\Controller\Api;

use App\Gomoku\Utils\GameHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{
    /**
     * @var GameHandler
     */
    private $gameHandler;

    public function __construct(GameHandler $gameHandler)
    {
        $this->gameHandler = $gameHandler;
    }

    public function save(Request $request): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true);

        $boardSize = $parameters['boardSize'] ?? null;

        return new JsonResponse($this->gameHandler->handleGameStart($boardSize), 201);
    }
}
