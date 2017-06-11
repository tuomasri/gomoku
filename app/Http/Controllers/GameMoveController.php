<?php

namespace App\Http\Controllers;

use App\Gomoku\Utils\GameHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GameMoveController extends Controller
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
    public function store(Request $request, $gameId)
    {
        return response(
            $this->gameHandler->addGameMove($request, $gameId),
            201
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
    public function destroy($gameId, $moveId)
    {
        return response(
            $this->gameHandler->undoGameMove($gameId, $moveId)
        );
    }
}
