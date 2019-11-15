<?php

namespace App\Controller\Api;

use App\Gomoku\Utils\GameHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameController extends AbstractController
{
    /**
     * @var GameHandler
     */
    private $gameHandler;

    /**
     * GameController constructor.
     * @param GameHandler $gameHandler
     */
    public function __construct(GameHandler $gameHandler)
    {
        $this->gameHandler = $gameHandler;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function save(Request $request)
    {
        $parameters = json_decode($request->getContent(), true);

        $boardSize = $parameters['boardSize'] ?? null;

        return new JsonResponse($this->gameHandler->handleGameStart($boardSize), 201);
    }
}
