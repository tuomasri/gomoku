<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.4.2018
 * Time: 15:15
 */

namespace App\Gomoku\Utils;


use Illuminate\Support\Collection;

class BoardDirection
{
    const DIRECTION_NORTH     = 'NORTH';
    const DIRECTION_NORTHEAST = 'NORTHEAST';
    const DIRECTION_EAST      = 'EAST';
    const DIRECTION_SOUTHEAST = 'SOUTHEAST';
    const DIRECTION_SOUTH     = 'SOUTH';
    const DIRECTION_SOUTHWEST = 'SOUTHWEST';
    const DIRECTION_WEST      = 'WEST';
    const DIRECTION_NORTHWEST = 'NORTHWEST';

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

    const DIRECTION_OPPOSITES = [
        self::DIRECTION_NORTH     => self::DIRECTION_SOUTH,
        self::DIRECTION_NORTHEAST => self::DIRECTION_SOUTHWEST,
        self::DIRECTION_EAST      => self::DIRECTION_WEST,
        self::DIRECTION_SOUTHEAST => self::DIRECTION_NORTHWEST,
        self::DIRECTION_SOUTH     => self::DIRECTION_NORTH,
        self::DIRECTION_SOUTHWEST => self::DIRECTION_NORTHEAST,
        self::DIRECTION_WEST      => self::DIRECTION_EAST,
        self::DIRECTION_NORTHWEST => self::DIRECTION_SOUTHEAST,
    ];

    /**
     * @return Collection
     */
    public static function asOppositePairs()
    {
        return self::getDirections()
            ->map(
                function (BoardDirection $direction) {
                    return Collection::make([
                        $direction,
                        $direction->toOppositeDirection()
                    ]);
                }
            );
    }

    /**
     * @return Collection
     */
    public static function getDirections()
    {
        return Collection::make(self::DIRECTIONS)
            ->map(
                function ($direction) {
                    return new self($direction);
                }
            );
    }

    /**
     * @var string
     */
    private $directionName;

    /**
     * BoardDirection constructor.
     * @param string $directionName
     */
    public function __construct($directionName)
    {
        if (! in_array($directionName, self::DIRECTIONS, true)) {
            throw new \InvalidArgumentException("Unknown direction");
        }

        $this->directionName = $directionName;
    }

    /**
     * @return BoardDirection
     */
    public function toOppositeDirection()
    {
        return new self(self::DIRECTION_OPPOSITES[$this->directionName]);
    }

    /**
     * @return string
     */
    public function getDirectionName()
    {
        return $this->directionName;
    }
}