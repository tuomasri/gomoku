<?php
/**
 * Created by PhpStorm.
 * User: tuomas
 * Date: 9.6.2017
 * Time: 1:07
 */

namespace App\Repository;

use App\Gomoku\Entity\Game;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class GameRepository extends EntityRepository
{
    /**
     * Palauttaa ID:n perusteella peli-instanssin tai heittää poikkeuksen
     *
     * @param int $gameId
     * @return Game
     * @throws NoResultException
     */
    public function findGameOrFail($gameId)
    {
        $query = $this
            ->createQueryBuilder('game')
            ->where('game.id = :gameId')
            ->setParameter('gameId', $gameId);

        return $query->getQuery()->getSingleResult();
    }
}