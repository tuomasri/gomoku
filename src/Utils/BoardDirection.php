<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 6.4.2018
 * Time: 15:15
 */

namespace App\Utils;

use Tightenco\Collect\Support\Collection;

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
     * @var string
     */
    private $directionName;

    private function __construct(string $directionName)
    {
        $this->directionName = $directionName;
    }

    public static function asOppositePairs(): Collection
    {
        return self::getDirections()->map(
            function (BoardDirection $direction) {
                return [$direction, $direction->toOppositeDirection()];
            }
        );
    }

    public static function getDirections(): Collection
    {
        return Collection::make(self::DIRECTIONS)->map(
            function ($direction) {
                return new self($direction);
            }
        );
    }

    public function toOppositeDirection(): BoardDirection
    {
        return new self(self::DIRECTION_OPPOSITES[$this->directionName]);
    }

    public function getDirectionName(): string
    {
        return $this->directionName;
    }
}
