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
     * @ORM\Column(name="is_terminated", type="boolean")
     *
     * @var bool
     */
    private $isTerminated;

    /**
     * @ORM\Column(type="smallint", name="board_size")
     *
     * @var int
     */
    private $boardSize;

    /**
     * Game constructor.
     * @param int $boardSize
     */
    private function __construct($boardSize)
    {
        $this->players = new ArrayCollection();
        $this->moves = new ArrayCollection();
        $this->boardSize = $boardSize;
        $this->isTerminated = false;
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
    public function isOngoing()
    {
        return ! $this->isTerminated;
    }

    /**
     * @return bool
     */
    public function isTerminated()
    {
        return $this->isTerminated;
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
        $this->resolveNewGameState($gameMove);

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
            'players' => Collection::make($this->players)->map->jsonSerialize()->all(),
            'moves'   => Collection::make($this->moves)->map->jsonSerialize()->all(),
            'winner'  => $this->winner ? $this->winner->jsonSerialize() : null,
            'isTerminated' => $this->isTerminated,
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

        $this->moves->add($gameMove);
    }

    /**
     * @param GameMove $lastGameMove
     */
    private function resolveNewGameState(GameMove $lastGameMove)
    {
        $winningGameMoves = (new GameMoveResolver())->getWinningGameMoves($lastGameMove);

        $this->isTerminated =
            ! $winningGameMoves->isEmpty() ||  // Voitto
            $this->moves->count() === $this->getMaxNumberOfTurns(); // Tasapeli

        if (! $winningGameMoves->isEmpty()) {
            $this->winner = $lastGameMove->getPlayer();
            $winningGameMoves->each(
                function (GameMove $gameMove) {
                    $gameMove->flagAsWinningMove();
                }
            );
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    private function canAddPlayer(Player $player)
    {
        if (! $this->isOngoing() || $this->players->count() > 1) {
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

        /** @var GameMove $lastMove */
        $lastMove = $this->moves->last();

        if ($lastMove && $gameMove->isByPlayer($lastMove->getPlayer())) {
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

        $lastMove = $this->moves->last();

        if (! ($lastMove && $lastMove->hasId($moveId))) {
            $throwException("only latest move is undoable");
        }

        if ($lastMove->getDateCreated()->diff(new \DateTime())->s > self::MOVE_UNDO_THRESHOLD) {
            $throwException("move is not within undo threshold");
        }

        return $lastMove;
    }
}