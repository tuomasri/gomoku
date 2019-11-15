<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.6.2017
 * Time: 18:14
 */

namespace App\Gomoku\Entity;

use App\Gomoku\Utils\BoardDirection;
use App\Gomoku\Utils\GameMoveResolver;
use App\Gomoku\Utils\NeighbourMoveDTO;
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

    /**
     * GameMove constructor.
     * @param Game $game
     * @param Player $player
     * @param int $x
     * @param int $y
     * @throws \InvalidArgumentException, \LogicException
     */
    public function __construct(Game $game, Player $player, $x, $y)
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

    /**
     * Palauttaa siirron koordinaatit muodossa X.Y
     *
     * @return string
     */
    public function getRepresentation()
    {
        return $this->x . $this->y;
    }

    /**
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function isInPosition($x, $y)
    {
        return $this->x === $x && $this->y === $y;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function hasId($id)
    {
        return $this->id === $id;
    }

    /**
     * TRUE jos $gameMove on saman pelaajan siirto
     *
     * @param Player $player
     * @return bool
     */
    public function isByPlayer(Player $player)
    {
        return $this->player->hasId($player->getId());
    }

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Liputtaa siirron voittavaksi (näitä muodostuu siis 5 voittotilanteessa)
     */
    public function toWinningMove()
    {
        $this->isWinningMove = true;
    }

    /**
     * Palauttaa naapurisiirron ID:n tietystä suunnasta tai NULL jos naapuria ei ole
     *
     * @param BoardDirection $direction
     * @return int|null
     */
    public function getNeighbourMoveIdInDirection(BoardDirection $direction)
    {
        return $this->neighbours[$direction->getDirectionName()] ?? null;
    }

    /**
     * @param BoardDirection $direction
     * @return Collection<GameMove>
     */
    public function getNeighboursInDirection(BoardDirection $direction)
    {
        return Collection::make(range(0, Game::WINNING_NUM_OF_MOVES - 1))
            ->reduce(
                function (Collection $collection, $offset) use ($direction) {
                    /** @var GameMove $gameMove */
                    $gameMove = $offset > 0 ? $collection->last() : $this;
                    $neighbourMoveId = $gameMove ? $gameMove->getNeighbourMoveIdInDirection($direction) : null;

                    return $collection->push($this->game->getMoveById($neighbourMoveId));
                },
                Collection::make()
            )
            ->filter()
            ->values();
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return clone $this->dateCreated;
    }

    /**
     * Linkittää naapurisiirrot keskenään (uutta siirtoa tehtäessä)
     */
    public function linkNeighbourMoves()
    {
        (new GameMoveResolver())->getSurroundingNeighbourMoves($this)
            ->each(
                function (NeighbourMoveDTO $dto) {
                    // Tästä siirrosta linkki naapurisiirtoon
                    $this->setNeighbourInDirection($dto->gameMove, $dto->boardDirection);

                    // Vastakkainen linkki myös naapurista tähän siirtoon
                    $dto->gameMove->setNeighbourInDirection($this, $dto->boardDirection->toOppositeDirection());
                }
            );
    }

    /**
     * Poistaa naapurisiirtolinkityksen (siirtoa kumottaessa)
     */
    public function unlinkNeighbourMoves()
    {
        (new GameMoveResolver())->getSurroundingNeighbourMoves($this)
            ->each(
                function (NeighbourMoveDTO $dto) {
                    $dto->gameMove->resetNeighbourInDirection($dto->boardDirection->toOppositeDirection());
                }
            );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
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

    /**
     * Asettaa parametrina annetun siirron tämän siirron naapuriksi.
     *
     * @param GameMove $gameMove
     * @param BoardDirection $direction
     */
    private function setNeighbourInDirection(GameMove $gameMove, BoardDirection $direction)
    {
        $this->neighbours[$direction->getDirectionName()] = $gameMove->getId();
    }

    /**
     * Poistaa parametrina annetussa suunnassa olevan siirron tämän siirron naapureista
     *
     * @param BoardDirection $direction
     */
    private function resetNeighbourInDirection(BoardDirection $direction)
    {
        unset($this->neighbours[$direction->getDirectionName()]);
    }

    /**
     * @param Game $game
     * @param $x
     * @param $y
     */
    private function validateCoordinateValues(Game $game, $x, $y)
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
}