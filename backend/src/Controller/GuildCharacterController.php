<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\CharacterDTO;
use App\Entity\GameCharacter;
use App\Entity\User;
use App\Form\GameCharacterType;
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
    ) {}

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

    #[Route('api/gamecharacter/create', name: 'guild_character_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function guildCharacterCreate(Request $request): JsonResponse
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
}
