<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.6.2017
 * Time: 17:10
 */

namespace App\Gomoku\Entity;

use App\Gomoku\Utils\GameMoveResolver;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Tightenco\Collect\Support\Collection;

/**
 * Pelin tietomalli
 *
 * @ORM\Entity(repositoryClass="App\Repository\GameRepository")
 * @ORM\Table(name="game")
 *
 * Class Game
 * @package App\Gomoku\Model
 */
class Game implements \JsonSerializable
{
    /**
     * Uusi peli (ei sisällä ainuttakaan siirtoa)
     */
    const STATE_STARTED = 1;

    /**
     * Aloitettu peli (1. siirto tehty)
     */
    const STATE_ONGOING = 2;

    /**
     * Päättynyt peli (voittaja selvillä tai tasapeli)
     */
    const STATE_ENDED = 3;

    /**
     * 5 vierekkäisellä siirrolla (pystyyn, vaakaan & viistosti) voittaa pelin
     */
    const WINNING_NUM_OF_MOVES = 5;



    /**
     * Viimeisimmän siirron perumiseen on aikaa 5 sekuntia siirron tekemisestä
     */
    const MOVE_UNDO_THRESHOLD = 5;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     *
     * @var int
     */
    private $state;

    /**
     * Peliin linkatut pelaajat
     *
     * @ORM\OneToMany(targetEntity="Player", mappedBy="game", cascade={"persist"})
     * @ORM\OrderBy({"color" = "ASC"})
     *
     * @var ArrayCollection
     */
    private $players;

    /**
     * Pelin siirrot
     *
     * @ORM\OneToMany(targetEntity="GameMove", mappedBy="game", cascade={"persist"})
     * @ORM\OrderBy({"id" = "ASC"})
     *
     * @var ArrayCollection
     */
    private $moves;

    /**
     * Pelin voittaja
     *
     * @ORM\ManyToOne(targetEntity="Player")
     * @ORM\JoinColumn(name="winner_id", referencedColumnName="id")
     *
     * @var Player|null
     */
    private $winner;

    /**
     * @ORM\Column(type="smallint", name="board_size")
     *
     * @var int
     */
    private $boardSize;

    public function __construct($boardSize)
    {
        $this->state = self::STATE_STARTED;
        $this->players = new ArrayCollection();
        $this->moves = new ArrayCollection();
        $this->boardSize = $boardSize;
    }

    /**
     * @param int $boardSize
     * @return Game
     */
    public static function startGame($boardSize)
    {
        $instance = new self($boardSize);

        $instance->addPlayer(Player::createBlackPlayer());
        $instance->addPlayer(Player::createWhitePlayer());

        return $instance;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * @return int
     */
    public function getBoardSize()
    {
        return $this->boardSize;
    }

    /**
     * @param int $playerId
     * @return Player
     * @throws \RuntimeException
     */
    public function getPlayerById($playerId)
    {
        return Collection::make($this->players)
            ->filter(
                function (Player $player) use ($playerId) {
                    return $player->hasId($playerId);
                }
            )
            ->tap(
                function (Collection $playerCollection) use ($playerId) {
                    if ($playerCollection->isEmpty()) {
                        throw new \RuntimeException(
                            __CLASS__ . ": player #{$playerId} was not found."
                        );
                    }
                }
            )
            ->first();
    }

    /**
     * @return ArrayCollection
     */
    public function getMoves()
    {
        return $this->moves;
    }

    /**
     * @return bool
     */
    public function isTerminated()
    {
        return $this->state === self::STATE_ENDED;
    }

    /**
     * @return bool
     */
    public function isOngoing()
    {
        return $this->state === self::STATE_ONGOING;
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return $this->state === self::STATE_STARTED;
    }

    /**
     * @param Player $player
     * @throws \DomainException
     */
    public function addPlayer(Player $player)
    {
        if (! $this->canAddPlayer($player)) {
            throw new \DomainException(
                __CLASS__ . ": unable to add player into game #{$this->id}"
            );
        }

        $this->players->add($player);

        $player->setGame($this);
    }

    /**
     * @param GameMove $gameMove
     * @param \Closure $flushCallback
     */
    public function handleGameMoveAdded(GameMove $gameMove, \Closure $flushCallback)
    {
            // Siirron validointi & tallennus
            $this->addGameMove($gameMove);

            $flushCallback();

            // Lisätyn siirron naapurien ja mahd. voittajan resolvointi
            $gameMove->linkNeighbourMoves();
            $this->resolveGameState($gameMove);

            $flushCallback();
    }

    /**
     * Siirron peruminen (sallii ainoastaan viimeisimmän siirron perumisen).
     * Ei tietty välttämättä tarvitsisi ID:tä parametrina.
     *
     * @param int $moveId
     * @return GameMove
     * @throws \DomainException, \RuntimeException
     */
    public function undoGameMove($moveId)
    {
        $gameMove = $this->getUndoableMove($moveId);

        $gameMove->unlinkNeighbourMoves();

        $this->moves->removeElement($gameMove);

        $this->state = $this->moves->isEmpty() ? self::STATE_STARTED : self::STATE_ONGOING;

        return $gameMove;
    }

    /**
     * @param int $moveId
     * @return GameMove|null
     */
    public function getMoveById($moveId)
    {
        return ! $moveId
            ? null
            : Collection::make($this->moves->toArray())
                ->first(
                    function (GameMove $gameMove) use ($moveId) {
                        return $gameMove->hasId($moveId);
                    }
                );
    }

    /**
     * Palauttaa pelin siirron kohdassa x, y tai NULL jos ei löydy
     *
     * @param int $x
     * @param int $y
     * @param Player|null $player
     * @return GameMove|null
     */
    public function getMoveInPosition($x, $y, Player $player = null)
    {
        $isWithinBoard = function ($position) {
            return $position >= 0 && $position < $this->boardSize;
        };

        return ! ($isWithinBoard($x) && $isWithinBoard($y))
            ? null
            : Collection::make($this->moves->toArray())
                ->first(
                    function (GameMove $gameMove) use ($x, $y, $player) {
                        return
                            $gameMove->isInPosition($x, $y) &&
                            (! $player || $gameMove->isByPlayer($player));
                    }
                );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id'      => $this->id,
            'state'   => $this->state,
            'players' => Collection::make($this->players)->map->jsonSerialize()->all(),
            'moves'   => Collection::make($this->moves)->map->jsonSerialize()->all(),
            'winner'  => $this->winner ? $this->winner->jsonSerialize() : null,
        ];
    }

    private function getMaxNumberOfTurns()
    {
        return $this->boardSize * $this->boardSize;
    }

    /**
     * @param GameMove $gameMove
     * @throws \DomainException
     */
    private function addGameMove(GameMove $gameMove)
    {
        $this->validateAddMove($gameMove);

        /** @var GameMove $lastMove */
        $lastMove = $this->moves->last();

        $this->moves->add($gameMove);
    }

    /**
     * @param GameMove $lastGameMove
     */
    private function resolveGameState(GameMove $lastGameMove)
    {
        $winningGameMoves = (new GameMoveResolver())->getWinningGameMoves($lastGameMove);

        // Voittaja selvillä
        if ($winningGameMoves->isNotEmpty()) {
            $this->state = self::STATE_ENDED;
            $this->winner = $lastGameMove->getPlayer();
            $winningGameMoves->each->toWinningMove();
        }
        // Tasapeli
        else if ($this->moves->count() === $this->getMaxNumberOfTurns()) {
            $this->state = self::STATE_ENDED;
        }
        // Peli jatkuu
        else {
            $this->state = self::STATE_ONGOING;
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    private function canAddPlayer(Player $player)
    {
        if (! $this->isStarted() || $this->players->count() > 1) {
            return false;
        }

        /** @var Player $currentPlayer */
        $currentPlayer = $this->players->first();

        return $currentPlayer ? ! $currentPlayer->isSameColor($player) : true;
    }

    /**
     * @param GameMove $gameMove
     * @throws \DomainException
     */
    private function validateAddMove(GameMove $gameMove)
    {
        if ($this->isTerminated() || $this->moves->count() === $this->getMaxNumberOfTurns()) {
            throw new \DomainException(
                __CLASS__ . ": terminated game or max number of turns"
            );
        }

        // Jos aloitettu peli niin tiedetään, että ei vielä siirtoja mutta lisäävän pelaajan pitää olla musta
        if ($this->isStarted() && ! $gameMove->getPlayer()->isBlack()) {
            throw new \DomainException(
                __CLASS__ . ": was expecting black player"
            );
        }

        if ($this->isOngoing() && $gameMove->getPlayer()->isSameColor($this->moves->last()->getPlayer())) {
            throw new \DomainException(
                __CLASS__ . ": was not expecting player of same colour "
            );
        }

        // Menossa oleva peli eli pitää varmistaa, että pelilauta on siirron kohdalla tyhjä & pelaaja on oikea
        if ($this->getMoveInPosition($gameMove->getX(), $gameMove->getY())) {
            throw new \DomainException(
                __CLASS__ . ": has already move in position"
            );
        }
    }

    /**
     * @param int $moveId
     * @return GameMove
     * @throws \DomainException
     */
    private function getUndoableMove($moveId)
    {
        $throwException = function ($message) {
            throw new \DomainException(
                __CLASS__ . ": move is not undoable (" . $message . ")"
            );
        };

        if (! $this->isOngoing()) {
            $throwException("game is not ongoing");
        }

        $lastMove = $this->moves->last();

        if (! $lastMove->hasId($moveId)) {
            $throwException("only latest move is undoable");
        }

        if ($lastMove->getDateCreated()->diff(new \DateTime())->s > self::MOVE_UNDO_THRESHOLD) {
            $throwException("move is not within undo threshold");
        }

        return $lastMove;
    }
}