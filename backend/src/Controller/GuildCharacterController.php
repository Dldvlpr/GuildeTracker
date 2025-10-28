<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\CharacterDTO;
use App\Entity\GameCharacter;
use App\Entity\User;
use App\Form\GameCharacterType;
use App\Repository\GameCharacterRepository;
use App\Repository\GameGuildRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class GuildCharacterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GameGuildRepository $gameGuildRepository,
        private readonly UserRepository $userRepository,
        private readonly GameCharacterRepository $gameCharacterRepository
    ) {}

    #[Route('api/characters/{id}', name: 'api_character_show', methods: ['GET'])]
    public function showCharacter(GameCharacter $character): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // $this->denyAccessUnlessGranted('CHARACTER_VIEW', $character);

        return $this->json(CharacterDTO::fromEntity($character));
    }

    #[Route('api/characters', name: 'api_character_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function createCharacter(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        if (!isset($payload['guildId']) || empty($payload['guildId'])) {
            return $this->json(['error' => 'guildId is required'], 422);
        }

        $guild = $this->gameGuildRepository->find($payload['guildId']);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], 404);
        }

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);

        $securityUser = $this->getUser();
        if (!$securityUser) {
            return $this->json(['error' => 'Unauthenticated'], 401);
        }

        $user = $this->userRepository->findOneBy(['discordId' => $securityUser->getDiscordId()]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $formData = [
            'name' => $payload['name'] ?? null,
            'class' => $payload['class'] ?? null,
            'classSpec' => $payload['classSpec'] ?? null,
            'role' => $payload['role'] ?? null,
        ];

        $gameCharacter = new GameCharacter();
        $gameCharacter->setUserPlayer($user);
        $gameCharacter->setGuild($guild);

        $form = $this->createForm(GameCharacterType::class, $gameCharacter, [
            'csrf_protection' => false,
        ]);
        $form->submit($formData, false);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation failed',
                'details' => $errors,
                'violations' => $errors
            ], 422);
        }

        try {
            $this->em->persist($gameCharacter);
            $this->em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Database error', 'hint' => $e->getMessage()], 500);
        }

        return $this->json([
            'status' => 'ok',
            'id' => $gameCharacter->getUuidToString(),
            'character' => CharacterDTO::fromEntity($gameCharacter)
        ], 201);
    }

    #[Route('/api/characters/{id}', name: 'api_character_delete', methods: ['DELETE'])]
    public function deleteCharacter(GameCharacter $character): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if ($character->getUserPlayer()?->getId() !== $user->getId()) {
            $guild = $character->getGuild();
            if (!$guild) {
                return new JsonResponse(['error' => 'Character does not belong to any guild'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'You do not have permission to delete this character'], Response::HTTP_FORBIDDEN);
            }
        }

        try {
            $this->em->remove($character);
            $this->em->flush();

            return new JsonResponse([
                'status' => 'ok',
                'message' => 'Character successfully deleted'
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Failed to delete character',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
