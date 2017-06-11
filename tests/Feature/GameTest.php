<?php

namespace Tests\Feature;

use Tests\TestCase;

class GameTest extends TestCase
{
    /**
     * Testi pelin aloitukselle
     */
    public function testGameStart()
    {
        $response = $this->json('POST', '/api/game');

        $response
            ->assertStatus(201)
            ->assertJson([
                'state'  => 1,
                'winner' => null,
                'moves'  => [],
            ])
            ->assertJsonStructure([
                'players' => [
                    '*' => ['id', 'color'],
                ]
            ]);
    }

    /**
     * Testi siirron tekemiselle
     */
    public function testMakeGameMove()
    {
        $response = $this->json('POST', '/api/game');

        $game = $response->decodeResponseJson();
        $gameId = $game['id'];
        $blackPlayerId = $game['players'][0]['id'];

        $response = $this->createGameMove($gameId, $blackPlayerId, 1, 2);

        $response
            ->assertStatus(201)
            ->assertJson([
                'state'  => 2,
                'winner' => null,
            ])
            ->assertJsonStructure([
                'moves' => [
                    '*' => ['id', 'x', 'y', 'dateCreated', 'isWinningMove', 'playerId',],
                ]
            ]);

        $moveData = $response->decodeResponseJson()['moves'][0];

        $this->assertEquals(1, $moveData['x']);
        $this->assertEquals(2, $moveData['y']);
        $this->assertEquals(false, $moveData['isWinningMove']);
        $this->assertEquals($blackPlayerId, $moveData['playerId']);
    }

    /**
     * Testi virheellisen siirron tekemiselle (valkoinen pelaaja yrittää asettaa kiven varattuun soluun)
     */
    public function testMakeConflictingMove()
    {
        $game = $this->createGame();

        $gameId = $game['id'];
        $blackPlayerId = $game['players'][0]['id'];
        $whitePlayerId = $game['players'][1]['id'];

        $this->createGameMove($gameId, $blackPlayerId, 0, 0);
        $conflictingMove = $this->createGameMove($gameId, $whitePlayerId, 0, 0);

        $conflictingMove->assertStatus(500);
    }

    /**
     * Testi virheellisen siirron tekemiselle (musta pelaaja yrittää siirtää kaksi kertaa peräkkäin)
     */
    public function testMakeSamePlayerMove()
    {
        $game = $this->createGame();
        $blackPlayerId = $game['players'][0]['id'];

        $this->createGameMove($game['id'], $blackPlayerId, 0, 0);
        $conflictingMove = $this->createGameMove($game['id'], $blackPlayerId, 0, 1);

        $conflictingMove->assertStatus(500);
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa pystylinjalla)
     */
    public function testVerticalWin()
    {
        $game = $this->createGame();

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

        $winningMove = $this->createGameMove($game['id'], $blackPlayerId, 0, 4);

        $winningMove
            ->assertStatus(201)
            ->assertJson([
                'state'  => 3,
            ])
            ->assertJsonStructure([
                'winner' => ['id', 'color'],
            ]);

        $responseData = $winningMove->decodeResponseJson();

        $this->assertEquals($blackPlayerId, $responseData['winner']['id']);
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa vaakalinjalla)
     */
    public function testHorizontalWin()
    {
        $game = $this->createGame();

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

        $winningMove = $this->createGameMove($game['id'], $blackPlayerId, 5, 0);

        $winningMove
            ->assertStatus(201)
            ->assertJson([
                'state'  => 3,
            ])
            ->assertJsonStructure([
                'winner' => ['id', 'color'],
            ]);

        $responseData = $winningMove->decodeResponseJson();

        $this->assertEquals($blackPlayerId, $responseData['winner']['id']);
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa viistottaisella linjalla)
     */
    public function testSlantedWin()
    {
        $game = $this->createGame();

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

        $winningMove = $this->createGameMove($game['id'], $blackPlayerId, 5, 4);

        $winningMove
            ->assertStatus(201)
            ->assertJson([
                'state'  => 3,
            ])
            ->assertJsonStructure([
                'winner' => ['id', 'color'],
            ]);

        $responseData = $winningMove->decodeResponseJson();

        $this->assertEquals($blackPlayerId, $responseData['winner']['id']);
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa yhdistämällä 2 + 2 siirtoa)
     */
    public function testMergeWin()
    {
        $game = $this->createGame();

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

        $winningMove = $this->createGameMove($game['id'], $blackPlayerId, 3, 0);

        $winningMove
            ->assertStatus(201)
            ->assertJson([
                'state'  => 3,
            ])
            ->assertJsonStructure([
                'winner' => ['id', 'color'],
            ]);

        $responseData = $winningMove->decodeResponseJson();

        $this->assertEquals($blackPlayerId, $responseData['winner']['id']);
    }

    /**
     * Testi voittamiselle (musta pelaaja voittaa yhdistämällä 1 + 3 siirtoa)
     */
    public function testAnotherMergeWin()
    {
        $game = $this->createGame();

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

        $winningMove = $this->createGameMove($game['id'], $blackPlayerId, 2, 0);

        $winningMove
            ->assertStatus(201)
            ->assertJson([
                'state'  => 3,
            ])
            ->assertJsonStructure([
                'winner' => ['id', 'color'],
            ]);

        $responseData = $winningMove->decodeResponseJson();

        $this->assertEquals($blackPlayerId, $responseData['winner']['id']);
    }

    /**
     * @return array
     */
    private function createGame()
    {
        $response = $this->json('POST', '/api/game');

        return $response->decodeResponseJson();
    }

    /**
     * @param int $gameId
     * @param int $playerId
     * @param int $x
     * @param int $y
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    private function createGameMove($gameId, $playerId, $x, $y)
    {
        $route = "/api/game/{$gameId}/moves";

        return $this->json('POST', $route, [
            'x' => $x,
            'y' => $y,
            'player_id' => $playerId,
        ]);
    }
}

