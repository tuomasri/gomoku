<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.6.2017
 * Time: 18:14
 */

namespace App\Gomoku\Model;

use Illuminate\Support\Collection;
use Doctrine\ORM\Mapping as ORM;

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
     * Suunnat
     */
    const DIRECTION_NORTH     = 'NORTH';
    const DIRECTION_NORTHEAST = 'NORTHEAST';
    const DIRECTION_EAST      = 'EAST';
    const DIRECTION_SOUTHEAST = 'SOUTHEAST';
    const DIRECTION_SOUTH     = 'SOUTH';
    const DIRECTION_SOUTHWEST = 'SOUTHWEST';
    const DIRECTION_WEST      = 'WEST';
    const DIRECTION_NORTHWEST = 'NORTHWEST';

    /**
     * @var string[]
     */
    const DIRECTIONS = [
        self::DIRECTION_NORTH,
        self::DIRECTION_NORTHEAST,
        self::DIRECTION_EAST,
        self::DIRECTION_SOUTHEAST,
        self::DIRECTION_SOUTH,
        self::DIRECTION_SOUTHWEST,
        self::DIRECTION_WEST,
        self::DIRECTION_NORTHWEST,
    ];

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
        /**
         * Ei anneta instantioida pelilaudan kannalta mahdotonta siirtoa.
         * Huomaa: pelilauta alkaa vasemmasta yläkulmasta ( = 0,0)
         */
        $this->validateCoordinateValues([$x, $y]);

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
     * TRUE jos $gameMove on saman pelaajan siirto
     *
     * @param GameMove $gameMove
     * @return bool
     */
    public function isBySamePlayer(GameMove $gameMove)
    {
        return $this->player->hasId($gameMove->getPlayer()->getId());
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
     * @param string $direction
     * @return int|null
     */
    public function getNeighbourMoveIdInDirection($direction)
    {
        return $this->neighbours[$direction] ?? null;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return clone $this->dateCreated;
    }

    /**
     * Asettaa parametrina annetun siirron tämän siirron naapuriksi.
     *
     * @param GameMove $gameMove
     * @param string $direction
     * @throw \LogicException
     */
    public function setNeighbourInDirection(GameMove $gameMove, $direction)
    {
        if (! $this->isBySamePlayer($gameMove)) {
            throw new \LogicException(
                __CLASS__ . ': unable to set neighbours with different players'
            );
        }

        $this->neighbours[$direction] = $gameMove->getId();
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
     * @param int[] $values
     * @throws \InvalidArgumentException, \DomainException
     */
    private function validateCoordinateValues(array $values)
    {
        Collection::make($values)
            ->each(
                function ($value) {
                    if (! is_int($value)) {
                        throw new \InvalidArgumentException(
                            __CLASS__ . " coordinate value {$value} is not an integer"
                        );
                    }

                    // Ei anneta instantioida siirtoa joka menee pelipöydän rajojen yli
                    if ($value < 0 || $value > Game::BOARD_SIZE - 1) {
                        throw new \DomainException(
                            __CLASS__ . ": coordinate {$value} is not within game board"
                        );
                    }
                }
            );
    }
}