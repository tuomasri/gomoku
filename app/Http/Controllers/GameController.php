<?php

namespace App\Http\Controllers;

use App\Gomoku\Utils\GameHandler;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Response;

class GameController extends Controller
{
    /**
     * @var GameHandler
     */
    private $gameHandler;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * GameController constructor.
     * @param GameHandler $gameHandler
     * @param EntityManager $entityManager
     */
    public function __construct(GameHandler $gameHandler, EntityManager $entityManager)
    {
        $this->gameHandler = $gameHandler;
        $this->entityManager = $entityManager;
    }

    /**
     * Pelin aloitus
     *
     * @return Response
     */
    public function store()
    {
        return response($this->gameHandler->handleGameStart(), 201);
    }
}
