<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.6.2017
 * Time: 18:14
 */

namespace App\Gomoku\Entity;

use App\Gomoku\Utils\BoardDirection;
use App\Gomoku\Utils\BoardPosition;
use Doctrine\ORM\Mapping as ORM;
use Tightenco\Collect\Support\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(name="game_move")
 *
 * Class GameMove
 * @package App\Gomoku\Model
 */
class GameMove implements \JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $x;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $y;

    /**
     * TRUE jos siirto kuuluu voittavan siirron ryhmään
     *
     * @ORM\Column(name="is_winning_move", type="boolean")
     *
     * @var bool
     */
    private $isWinningMove;

    /**
     * Peli johon siirto kuuluu
     *
     * @ORM\ManyToOne(targetEntity="Game", inversedBy="moves")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id")
     *
     * @var Game
     */
    private $game;

    /**
     * Pelaaja jolle siirto kuuluu
     *
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="moves")
     * @ORM\JoinColumn(name="player_id", referencedColumnName="id")
     *
     * @var Player
     */
    private $player;

    /**
     * Seuraa kunkin siirron naapureita eli jos siirron vieressä on saman pelaajan muita
     * siirtoja niin linkataan nämä keskenään (käytössä voittajan resolvointia varten).
     * Voisi olla myös tietokantatason linkki mutta menee JSON-konstruktionakin.
     *
     * @ORM\Column(name="neighbours", type="json")
     *
     * @var array
     */
    private $neighbours;

    /**
     * @ORM\Column(name="date_created", type="datetime")
     *
     * @var \DateTime
     */
    private $dateCreated;

    public function __construct(Game $game, Player $player, int $x, int $y)
    {
        $this->validateCoordinateValues($game, $x, $y);

        $this->game = $game;
        $this->player = $player;
        $this->x = $x;
        $this->y = $y;
        $this->isWinningMove = false;
        $this->neighbours = [];
        $this->dateCreated = new \DateTime();
    }

    public function isInPosition(int $x, int $y): bool
    {
        return $this->x === $x && $this->y === $y;
    }

    public function matchesId(int $id): bool
    {
        return $this->id === $id;
    }

    public function isByPlayer(Player $player): bool
    {
        return $this->player->matchesId($player->getId());
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreated(): \DateTime
    {
        return clone $this->dateCreated;
    }

    public function getPosition(): array
    {
        return [$this->x, $this->y];
    }

    public function flagAsWinningMove(): void
    {
        $this->isWinningMove = true;
    }

    public function getNeighbourMovesInDirection(BoardDirection $direction): Collection
    {
        return Collection::make(range(0, Game::WINNING_NUM_OF_MOVES - 1))
            ->reduce(
                function (Collection $acc, $offset) use ($direction) {
                    /** @var GameMove $gameMove */
                    $gameMove = $offset > 0 ? $acc->last() : $this;
                    $neighbourMoveId = $gameMove ? $gameMove->getNeighbourMoveIdInDirection($direction) : null;

                    return $neighbourMoveId
                        ? $acc->push($this->game->getMoveById($neighbourMoveId))
                        : $acc;
                },
                Collection::make()
            )
            ->filter()
            ->values();
    }

    public function linkNeighbourMoves(): void
    {
        $this
            ->getSurroundingNeighbourMoves()
            ->each(
                function (array $gameMoveAndDirection) {
                    [$gameMove, $boardDirection] = $gameMoveAndDirection;

                    // Tästä siirrosta linkki naapurisiirtoon
                    $this->setNeighbourInDirection($gameMove, $boardDirection);

                    // Vastakkainen linkki myös naapurista tähän siirtoon
                    $gameMove->setNeighbourInDirection($this, $boardDirection->toOppositeDirection());
                }
            );
    }

    public function unlinkNeighbourMoves(): void
    {
        $this
            ->getSurroundingNeighbourMoves()
            ->each(
                function (array $gameMoveAndDirection) {
                    [$gameMove, $boardDirection] = $gameMoveAndDirection;

                    $gameMove->resetNeighbourInDirection($boardDirection->toOppositeDirection());
                }
            );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'x'             => $this->x,
            'y'             => $this->y,
            'isWinningMove' => $this->isWinningMove,
            'dateCreated'   => $this->dateCreated->format('Y-m-d H:i:s'),
            'playerId'      => $this->player->getId(),
        ];
    }

    private function setNeighbourInDirection(GameMove $gameMove, BoardDirection $direction): void
    {
        $this->neighbours[$direction->getDirectionName()] = $gameMove->getId();
    }

    private function resetNeighbourInDirection(BoardDirection $direction): void
    {
        unset($this->neighbours[$direction->getDirectionName()]);
    }

    private function validateCoordinateValues(Game $game, int $x, int $y): void
    {
        $assertIsInt = function ($value) {
            if (! is_int($value)) {
                throw new \InvalidArgumentException(
                    __CLASS__ . " coordinate value {$value} is not an integer"
                );
            }
        };

        $assertIsWithinBoard = function ($value) use ($game) {
            if ($value < 0 || $value > $game->getBoardSize() - 1) {
                throw new \DomainException(
                    __CLASS__ . ": coordinate {$value} is not within game board"
                );
            }
        };

        Collection::make([$x, $y])
            ->each($assertIsInt)
            ->each($assertIsWithinBoard);
    }

    private function getNeighbourMoveIdInDirection(BoardDirection $direction): ?int
    {
        return $this->neighbours[$direction->getDirectionName()] ?? null;
    }

    private function getSurroundingNeighbourMoves(): Collection
    {
        return Collection::make(BoardDirection::getDirections())->reduce(
            function (Collection $acc, BoardDirection $direction) {
                $newPosition = BoardPosition::advanceOneStep($this, $direction);

                $neighbourMove = $this
                    ->getGame()
                    ->getMoveInPosition($newPosition->x, $newPosition->y, $this->getPlayer());

                return $neighbourMove
                    ? $acc->push([$neighbourMove, $direction])
                    : $acc;
            },
            new Collection()
        );
    }
}