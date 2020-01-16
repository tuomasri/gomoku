<?php declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Game;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function save(Request $request): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true);

        $boardSize = $parameters['boardSize'] ?? null;

        $game = Game::initializeGame($boardSize);

        $this->objectManager->persist($game);

        $this->objectManager->flush();

        return new JsonResponse($game, 201);
    }
}
