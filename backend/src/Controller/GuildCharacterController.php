<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\CharacterDTO;
use App\Entity\GameCharacter;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GuildCharacterController extends AbstractController
{

    #[Route('api/guildcharacter/{id}', name: 'guild_character', methods: ['GET'])]
    public function guildCharacter(GameCharacter $character): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // $this->denyAccessUnlessGranted('CHARACTER_VIEW', $character);

        return $this->json(CharacterDTO::fromEntity($character));
    }
}
