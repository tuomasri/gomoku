<?php

namespace App\Tests;

use App\Gomoku\Entity\Game;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameTest extends WebTestCase
{
    private const GAME_SHAPE = [
        'id',
        'state',
        'players',
        'moves',
        'winner',
    ];

    private const PLAYER_SHAPE = [
        'id',
        'color',
    ];

    private const GAME_MOVE_SHAPE = [
        'id',
        'x',
        'y',
        'isWinningMove',
        'dateCreated',
        'playerId'
    ];

    private const GAME_MOVE_RESPONSE_SHAPE = [
        'id',
        'state',
        'players',
        'moves',
        'winner',
    ];

    public function testNewGame()
    {
        $response = $this->createGame();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $this->assertJson($response->getContent());

        $game = json_decode($response->getContent(), true);
        $players = $game['players'] ?? [];

        foreach (self::GAME_SHAPE as $property) {
            $this->assertArrayHasKey($property, $game);
        }

        foreach ($players as $player) {
            foreach (self::PLAYER_SHAPE as $property) {
                $this->assertArrayHasKey($property, $player);
            }
        }
    }

    public function testGameMove()
    {
        $response = $this->createGame();

        $game = json_decode($response->getContent(), true);

        $gameId = $game['id'];
        $blackPlayerId = $game['players'][0]['id'];

        $response = $this->createGameMove($gameId, $blackPlayerId, 1, 2);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $gameMove = json_decode($response->getContent(), true);
        $players = $gameMove['players'] ?? [];
        $player1 = $players[0] ?? [];
        $move = $gameMove['moves'][0] ?? null;

        foreach (self::GAME_MOVE_RESPONSE_SHAPE as $property) {
            $this->assertArrayHasKey($property, $gameMove);
        }

        foreach (self::PLAYER_SHAPE as $property) {
            foreach ($players as $player) {
                $this->assertArrayHasKey($property, $player);
            }
        }

        foreach (self::GAME_MOVE_SHAPE as $property) {
            $this->assertArrayHasKey($property, $move);
        }

        $this->assertEquals(1, $move['x']);

        $this->assertEquals(2, $move['y']);

        $this->assertEquals($player1['id'], $move['playerId']);
    }

    public function testMakeConflictingMove()
    {
        $response = $this->createGame();

        $game = json_decode($response->getContent(), true);

        $gameId = $game['id'];
        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($gameId, $blackPlayerId, 0, 0);

        $this->expectException(\DomainException::class);

        $response = $this->createGameMove($gameId, $whitePlayerId, 0, 0);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testMakeSamePlayerMove()
    {
        $game = json_decode($this->createGame()->getContent(), true);
        $blackPlayerId = $game['players'][0]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 0, 0);

        $this->expectException(\DomainException::class);
        $conflictingMove = $this->createGameMove($game['id'], $blackPlayerId, 0, 1);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $conflictingMove->getStatusCode());
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa pystylinjalla)
     */
    public function testVerticalWin()
    {
        $game = json_decode($this->createGame()->getContent(), true);

        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 0, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 1, 0);

        $this->createGameMove($game['id'], $blackPlayerId, 0, 1);
        $this->createGameMove($game['id'], $whitePlayerId, 2, 0);

        $this->createGameMove($game['id'], $blackPlayerId, 0, 2);
        $this->createGameMove($game['id'], $whitePlayerId, 3, 0);

        $this->createGameMove($game['id'], $blackPlayerId, 0, 3);
        $this->createGameMove($game['id'], $whitePlayerId, 4, 0);

        $response = $this->createGameMove($game['id'], $blackPlayerId, 0, 4);

        $winningMove = json_decode($response->getContent(), true);

        $winner = $winningMove['winner'] ?? null;

        foreach (self::PLAYER_SHAPE as $property) {
            $this->assertArrayHasKey($property, $winner);
        }

        $this->assertEquals($blackPlayerId, $winner['id']);
    }

    public function testHorizontalWin()
    {
        $game = json_decode($this->createGame()->getContent(), true);

        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 1, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 1, 1);

        $this->createGameMove($game['id'], $blackPlayerId, 2, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 2, 2);

        $this->createGameMove($game['id'], $blackPlayerId, 3, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 3, 3);

        $this->createGameMove($game['id'], $blackPlayerId, 4, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 4, 4);
        $response = $this->createGameMove($game['id'], $blackPlayerId, 5, 0);

        $winningMove = json_decode($response->getContent(), true);

        $winner = $winningMove['winner'] ?? null;

        $this->assertEquals($blackPlayerId, $winner['id']);
    }

    public function testSlantedWin()
    {
        $game = json_decode($this->createGame()->getContent(),true);

        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 1, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 1, 1);

        $this->createGameMove($game['id'], $blackPlayerId, 2, 1);
        $this->createGameMove($game['id'], $whitePlayerId, 2, 2);

        $this->createGameMove($game['id'], $blackPlayerId, 3, 2);
        $this->createGameMove($game['id'], $whitePlayerId, 3, 3);

        $this->createGameMove($game['id'], $blackPlayerId, 4, 3);
        $this->createGameMove($game['id'], $whitePlayerId, 4, 4);

        $response = $this->createGameMove($game['id'], $blackPlayerId, 5, 4);

        $winningMove = json_decode($response->getContent(), true);

        $winner = $winningMove['winner'] ?? null;

        $this->assertEquals($blackPlayerId, $winner['id']);
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa yhdistämällä 2 + 2 siirtoa)
     */
    public function testMergeWin()
    {
        $game = json_decode($this->createGame()->getContent(), true);

        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 1, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 1, 1);

        $this->createGameMove($game['id'], $blackPlayerId, 2, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 2, 2);

        $this->createGameMove($game['id'], $blackPlayerId, 4, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 3, 3);

        $this->createGameMove($game['id'], $blackPlayerId, 5, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 4, 4);

        $response = $this->createGameMove($game['id'], $blackPlayerId, 3, 0);

        $winningMove = json_decode($response->getContent(), true);

        $winner = $winningMove['winner'] ?? null;

        $this->assertEquals($blackPlayerId, $winner['id']);
    }

    public function testAnotherMergeWin()
    {
        $game = json_decode($this->createGame()->getContent(), true);

        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 1, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 1, 1);

        $this->createGameMove($game['id'], $blackPlayerId, 3, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 2, 2);

        $this->createGameMove($game['id'], $blackPlayerId, 4, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 3, 3);

        $this->createGameMove($game['id'], $blackPlayerId, 5, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 4, 4);

        $response = $this->createGameMove($game['id'], $blackPlayerId, 2, 0);

        $winningMove = json_decode($response->getContent(), true);

        $winner = $winningMove['winner'] ?? null;

        $this->assertEquals($blackPlayerId, $winner['id']);
    }

    public function testTie()
    {
        $game = json_decode($this->createGame(2)->getContent(), true);

        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 0, 0);
        $this->createGameMove($game['id'], $whitePlayerId, 0, 1);

        $this->createGameMove($game['id'], $blackPlayerId, 1, 0);
        $response = $this->createGameMove($game['id'], $whitePlayerId, 1, 1);

        $tieMove = json_decode($response->getContent(), true);

        $winner = $tieMove['winner'] ?? null;

        $this->assertEquals(Game::STATE_ENDED, $tieMove['state']);
        $this->assertEquals($winner, null);
    }

    /**
     * @param int|null $boardSize
     * @return Response
     */
    private function createGame($boardSize = null)
    {
        $requestBody = $boardSize ? ['boardSize' => $boardSize] : [];

        $client = static::createClient();

        $route = '/api/game';

        $client->request('POST', $route, [], [], [], json_encode($requestBody));

        return $client->getResponse();
    }

    /**
     * @param int $gameId
     * @param int $playerId
     * @param int $x
     * @param int $y
     * @return Response
     */
    private function createGameMove($gameId, $playerId, $x, $y)
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $route = sprintf("/api/game/%s/moves", $gameId);

        $requestBody = [
            'x' => $x,
            'y' => $y,
            'player_id' => $playerId,
        ];

        $client->request('POST', $route, [], [], [], json_encode($requestBody));

        return $client->getResponse();
    }
}
