<?php declare(strict_types=1);
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
use Zend\EventManager\Exception\DomainException;

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
    private function __construct(int $boardSize)
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
    public static function initializeGame(int $boardSize)
    {
        $instance = new self($boardSize);

        $blackPlayer = Player::createBlackPlayer($instance);
        $whitePlayer = Player::createWhitePlayer($instance);

        $instance->players->add($blackPlayer);
        $instance->players->add($whitePlayer);

        return $instance;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBoardSize(): int
    {
        return $this->boardSize;
    }

    public function getPlayerById(int $playerId): Player
    {
        return Collection::make($this->players)
            ->filter(
                function (Player $player) use ($playerId) {
                    return $player->matchesId($playerId);
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

    public function isOngoing(): bool
    {
        return ! $this->isTerminated;
    }

    public function isTerminated(): bool
    {
        return $this->isTerminated;
    }

    public function handleNewGameMove(GameMove $gameMove): \Generator
    {
        // Siirron validointi & tallennus
        $this->addGameMove($gameMove);

        yield true;

        // Lisätyn siirron naapurien ja mahd. voittajan resolvointi
        $gameMove->linkNeighbourMoves();
        $this->resolveNewGameState($gameMove);

        yield true;
    }

    public function undoLatestGameMove(): GameMove
    {
        /** @var GameMove $lastMove */
        $lastMove = $this->moves->last();

        if ($lastMove->getDateCreated()->diff(new \DateTime())->s > self::MOVE_UNDO_THRESHOLD) {
            throw new DomainException(__CLASS__ . ": move is not within undo threshold");
        }

        $lastMove->unlinkNeighbourMoves();

        $this->moves->removeElement($lastMove);

        return $lastMove;
    }

    public function getMoveById(int $moveId): ?GameMove
    {
        return Collection::make($this->moves->toArray())->first(
            function (GameMove $gameMove) use ($moveId) {
                return $gameMove->matchesId($moveId);
            }
        );
    }

    public function getMoveInPosition(int $x, int $y, Player $player = null): ?GameMove
    {
        $isWithinBoard = function ($position) {
            return $position >= 0 && $position < $this->boardSize;
        };

        return $isWithinBoard($x) && $isWithinBoard($y)
            ? Collection::make($this->moves->toArray())->first(
                function (GameMove $gameMove) use ($x, $y, $player) {
                    return
                        $gameMove->isInPosition($x, $y) &&
                        (! $player || $gameMove->isByPlayer($player));
                }
            )
            : null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'      => $this->id,
            'players' => Collection::make($this->players)->map->jsonSerialize()->all(),
            'moves'   => Collection::make($this->moves)->map->jsonSerialize()->all(),
            'winner'  => $this->winner ? $this->winner->jsonSerialize() : null,
            'isTerminated' => $this->isTerminated,
        ];
    }

    private function getMaxNumberOfTurns(): int
    {
        return $this->boardSize * $this->boardSize;
    }

    private function addGameMove(GameMove $gameMove): void
    {
        $this->assertValidNewGameMove($gameMove);

        $this->moves->add($gameMove);
    }

    private function resolveNewGameState(GameMove $lastGameMove): void
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

    private function assertValidNewGameMove(GameMove $gameMove): void
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
        [$x, $y] = $gameMove->getPosition();
        if ($this->getMoveInPosition($x, $y)) {
            throw new \DomainException(
                __CLASS__ . ": has already move in position"
            );
        }
    }
}