<?php

namespace App\Http\Controllers;

use App\Gomoku\Utils\GameHandler;
use Illuminate\Http\Response;

class GameController extends Controller
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
     * Pelin aloitus
     *
     * @return Response
     */
    public function store()
    {
        return response($this->gameHandler->handleGameStart(), 201);
    }
}
