<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.6.2017
 * Time: 17:10
 */

namespace App\Gomoku\Model;

use App\Gomoku\Utils\BoardPosition;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Collection;

/**
 * Pelin tietomalli
 *
 * @ORM\Entity(repositoryClass="App\Gomoku\Repository\GameRepository")
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
     * Laudan koko per sivu
     */
    const BOARD_SIZE = 15;

    /**
     * Maksimisiirrot per peli
     */
    const MAX_NUMBER_OF_TURNS = self::BOARD_SIZE * self::BOARD_SIZE;

    /**
     * Viimeisimmän siirron perumiseen on aikaa 5 sekuntia siirron tekemisestä
     */
    const MOVE_UNDO_THRESHOLD = 5;

    /**
     * @ORM\Id()
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


    public function __construct()
    {
        $this->state = self::STATE_STARTED;
        $this->players = new ArrayCollection();
        $this->moves = new ArrayCollection();
    }

    /**
     * @return Game
     */
    public static function startGame()
    {
        $instance = new self();

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
     * Siirron lisäämisen 1. vaihe: lisätään tehty siirto peliin
     *
     * @param GameMove $gameMove
     * @throws \DomainException
     */
    public function addMove(GameMove $gameMove)
    {
        if (! $this->canAddMove($gameMove)) {
            throw new \DomainException(
                __CLASS__ . ": unable to add move into game"
            );
        }

        $this->moves->add($gameMove);
    }

    /**
     * Siirron lisäämisen 2. vaihe (tässä vaiheessa lisätty siirto tallennettu jo tietokantaan)
     *  - linkataan naapurisolut
     *  - selvitellään mahd. voittaja
     *
     * @return int
     * @throws \RuntimeException, \LogicException
     */
    public function resolveLastMoveLinksAndGameState()
    {
        if ($this->isTerminated()) {
            throw new \LogicException(
                __CLASS__ . ": game #{$this->id} is terminated"
            );
        }

        $lastMove = $this->moves->last();

        if ($lastMove) {
            $this->resolveNeighbours($lastMove);

            $this->resolveGameState($lastMove);
        }

        return $this->state;
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
        if (! $this->canUndoMove($moveId)) {
            throw new \DomainException(
                __CLASS__ . ": move #{$moveId} is not undoable"
            );
        }

        $move = $this
            ->moves
            ->filter(function (GameMove $gameMove) use ($moveId) {
                return $gameMove->getId() === $moveId;
            })
            ->first();

        $this->moves->removeElement($move);

        $this->state = $this->moves->isEmpty() ? self::STATE_STARTED : self::STATE_ONGOING;

        return $move;
    }

    /**
     * Palauttaa pelin siirron kohdassa x, y tai NULL jos ei löydy
     *
     * @param int $x
     * @param int $y
     * @return GameMove|null
     */
    public function getGameMoveInPosition($x, $y)
    {
        return $this->mapGameMovesByCoordinates()->get($x . $y);
    }

    /**
     * Palauttaa TRUE jos siirron edustamalla kohdalla on jo siirto tässä pelissä
     *
     * @param GameMove $gameMove
     * @return bool
     */
    public function containsMoveInPosition(GameMove $gameMove)
    {
        return $this->mapGameMovesByCoordinates()->has($gameMove->getRepresentation());
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

    /**
     * @param GameMove $latestGameMove
     */
    private function resolveNeighbours(GameMove $latestGameMove)
    {
        Collection::make(GameMove::DIRECTIONS)
            /*
             * Liikutaan tehdystä siirrosta 1 askel jokaiseen ilmansuuntaan ja katsotaan onko
             * solussa saman pelaajan tekemä siirto. Jos on, niin palautetaan ko. siirto
             */
            ->mapWithKeys(
                function ($direction) use ($latestGameMove) {
                    $newPosition = BoardPosition::createFromDirection($latestGameMove, $direction, 1);
                    $neighbourMove = $newPosition
                        ? $this->getGameMoveInPosition($newPosition->x, $newPosition->y)
                        : null;

                    return $neighbourMove && $neighbourMove->isBySamePlayer($latestGameMove)
                        ? [$direction => $neighbourMove]
                        : [];
                }
            )
            ->filter()
            ->each(
                function (GameMove $neighbourMove, $direction) use ($latestGameMove) {
                    // Nykyisestä siirrosta suuntaan x lähdettäessä naapurina siis $neighbourMove
                    $latestGameMove->setNeighbourInDirection($neighbourMove, $direction);

                    // Vastakkainen linkki myös naapurista tähän siirtoon
                    $neighbourMove->setNeighbourInDirection(
                        $latestGameMove,
                        BoardPosition::getOppositeDirection($direction)
                    );
                }
            );
    }

    /**
     * @param GameMove $newestGameMove
     * @return int
     */
    private function resolveGameState(GameMove $newestGameMove)
    {
        $winner = $this->resolveWinner($newestGameMove);

        // Voittaja selvillä
        if ($winner) {
            $this->state = self::STATE_ENDED;
            $this->winner = $winner;
        }
        // Tasapeli
        else if ($this->moves->count() === self::MAX_NUMBER_OF_TURNS) {
            $this->state = self::STATE_ENDED;
        }
        // Peli jatkuu
        else {
            $this->state = self::STATE_ONGOING;
        }
    }

    /**
     * @param GameMove $newestGameMove
     * @return Player|null
     */
    private function resolveWinner(GameMove $newestGameMove)
    {
        return Collection::make(GameMove::DIRECTIONS)
            ->reduce(
                function (Player $winner = null, $direction) use ($newestGameMove) {
                    // Peli on jo päättynyt joten ei tarkistella sen pitemmälle
                    if ($winner instanceof Player) {
                        return $winner;
                    }

                    // Jos äskettäin tehdyllä siirrolla on 4 naapurisiirtoa niin voittaja on selvillä
                    if ($this->flagWinningGameMoves($newestGameMove, $direction)) {
                        return $newestGameMove->getPlayer();
                    }

                    return null;
                }
            );
    }

    /**
     * @param GameMove $newestGameMove
     * @param int $direction
     * @return bool
     */
    private function flagWinningGameMoves(GameMove $newestGameMove, $direction)
    {
        // Key-value-taulu [siirron id => siirto]
        $lookup = $this->mapGameMovesById();

        /** @var Collection $neighbours */
        $neighbours =
            Collection::make([
                $direction,
                BoardPosition::getOppositeDirection($direction)
            ])
            ->reduce(
                function (Collection $winningNeighbours, $direction) use ($lookup, $newestGameMove) {
                    if ($winningNeighbours->count() === self::WINNING_NUM_OF_MOVES - 1) {
                        return $winningNeighbours;
                    }

                    /**
                     * Ei ny maailman selkeintä koodia, mutta ideana jatkaa tehdystä siirrosta
                     * tiettyyn suuntaan jos naapurisiirtoja löytyy. Saisi todennäköisesti vähän
                     * nätimmäksi jos kukin siirto tietäisi (linkkaisi) naapurinsa tietokantatasolla
                     * (eikä JSON-taulukossa), jolloin ei tarvitsisi kikkailla lookup-taulun kanssa.
                     */
                    $gameMove = $newestGameMove;

                    for ($i = 0; $i < self::WINNING_NUM_OF_MOVES - 1; $i++) {
                        $nextNeighbourId = $gameMove->getNeighbourMoveIdInDirection($direction);
                        $nextNeighbour = $lookup->get($nextNeighbourId);

                        // Siirtojen ketju katkeaa joten tiedetään, että voittoa ei ole tulossa
                        if (! $nextNeighbour) {
                            break;
                        }

                        $winningNeighbours->push($nextNeighbour);
                        $gameMove = $nextNeighbour;
                    }

                    return $winningNeighbours;
                },
                new Collection()
            );

        if ($neighbours->count() !== self::WINNING_NUM_OF_MOVES - 1) {
            return false;
        }

        $newestGameMove->toWinningMove();
        $neighbours->each->toWinningMove();

        return true;
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
     * @return bool
     */
    private function canAddMove(GameMove $gameMove)
    {
        if ($this->isTerminated() || $this->moves->count() === self::MAX_NUMBER_OF_TURNS) {
            return false;
        }

        // Jos aloitettu peli niin tiedetään, että ei vielä siirtoja mutta lisäävän pelaajan pitää olla musta
        if ($this->isStarted()) {
            return $gameMove->getPlayer()->isBlack();
        }

        // Menossa oleva peli eli pitää varmistaa, että pelilauta on siirron kohdalla tyhjä & pelaaja on oikea
        return
            ! $this->containsMoveInPosition($gameMove) &&
            ! $gameMove->getPlayer()->isSameColor($this->moves->last()->getPlayer());
    }

    /**
     * @param int $moveId
     * @return bool
     * @throws \RuntimeException
     */
    private function canUndoMove($moveId)
    {
        if (! $this->isOngoing()) {
            return false;
        }

        /** @var GameMove $latestMove */
        $latestMove = $this->moves->last();

        if ($latestMove->getId() !== $moveId) {
            throw new \RuntimeException(
                __CLASS__ . ": only latest move #{$latestMove->getId()} is undoable"
            );
        }

        $dateCreated = $latestMove->getDateCreated();

        return $dateCreated->diff(new \DateTime())->s <= self::MOVE_UNDO_THRESHOLD;
    }

    /**
     * @return Collection
     */
    private function mapGameMovesByCoordinates()
    {
        return Collection::make($this->moves->toArray())
            ->mapWithKeys(
                function (GameMove $gameMove) {
                    return [$gameMove->getRepresentation() => $gameMove];
                }
            );
    }

    /**
     * @return Collection
     */
    private function mapGameMovesById()
    {
        return Collection::make($this->moves->toArray())
            ->mapWithKeys(
                function (GameMove $gameMove) {
                    return [$gameMove->getId() => $gameMove];
                }
            );
    }
}