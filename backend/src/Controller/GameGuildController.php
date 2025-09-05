<?php

namespace App\Controller;

use App\Entity\GameGuild;
use App\Form\GameGuildType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GameGuildController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/api/gameguild/create', name: 'gameGuild_create', methods: ['POST'])]
    public function createGameGuild(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        $gameGuild = new GameGuild();

        $form = $this->createForm(GameGuildType::class, $gameGuild, [
            'csrf_protection' => false,
        ]);

        $form->submit($payload, true);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->json([
                'error'   => 'Validation failed',
            ], 422);
        }

        try {
            $this->em->persist($gameGuild);
            $this->em->flush();
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Database error',
                'hint'  => $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'status' => 'ok',
            'id'     => $gameGuild->getId(),
        ], 201);
    }
}
