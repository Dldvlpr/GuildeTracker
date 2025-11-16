<?php

namespace App\Controller;

use App\Entity\RaidPlan;
use App\Entity\GameGuild;
use App\Repository\RaidPlanRepository;
use App\Repository\GameCharacterRepository;
use App\Repository\GameGuildRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/raid-plans', name: 'api_raid_plan_')]
class RaidPlanController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RaidPlanRepository $raidPlanRepository,
        private GameGuildRepository $guildRepository,
        private GameCharacterRepository $gameCharacterRepository,
    ) {}

    
    #[Route('/guild/{guildId}', name: 'list', methods: ['GET'])]
    public function list(string $guildId): JsonResponse
    {
        $guild = $this->guildRepository->find($guildId);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }



        $plans = $this->raidPlanRepository->findByGuild($guild);

        return $this->json([
            'plans' => array_map(fn($plan) => $this->serializePlan($plan), $plans)
        ]);
    }

    
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $plan = $this->raidPlanRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Raid plan not found'], Response::HTTP_NOT_FOUND);
        }



        return $this->json($this->serializePlan($plan));
    }

    
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $guildId = $data['guildId'] ?? null;
        if (!$guildId) {
            return $this->json(['error' => 'guildId is required'], Response::HTTP_BAD_REQUEST);
        }

        $guild = $this->guildRepository->find($guildId);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }



        $plan = new RaidPlan();
        $plan->setName($data['name'] ?? 'Untitled Raid Plan');
        $plan->setGuild($guild);
        $plan->setCreatedBy($user);
        $plan->setBlocks($data['blocks'] ?? []);
        $plan->setMetadata($data['metadata'] ?? null);
        $plan->setIsTemplate($data['isTemplate'] ?? false);
        $plan->setBossId($data['bossId'] ?? null);
        $plan->setRaidName($data['raidName'] ?? null);

        $this->em->persist($plan);
        $this->em->flush();

        return $this->json($this->serializePlan($plan), Response::HTTP_CREATED);
    }

    
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $plan = $this->raidPlanRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Raid plan not found'], Response::HTTP_NOT_FOUND);
        }



        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $plan->setName($data['name']);
        }

        if (isset($data['blocks'])) {
            $plan->setBlocks($data['blocks']);
        }

        if (isset($data['metadata'])) {
            $plan->setMetadata($data['metadata']);
        }

        if (isset($data['isTemplate'])) {
            $plan->setIsTemplate($data['isTemplate']);
        }

        if (isset($data['bossId'])) {
            $plan->setBossId($data['bossId']);
        }

        if (isset($data['raidName'])) {
            $plan->setRaidName($data['raidName']);
        }

        $this->em->flush();

        return $this->json($this->serializePlan($plan));
    }

    
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $plan = $this->raidPlanRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Raid plan not found'], Response::HTTP_NOT_FOUND);
        }



        $this->em->remove($plan);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    
    #[Route('/templates/public', name: 'templates', methods: ['GET'])]
    public function templates(): JsonResponse
    {
        $templates = $this->raidPlanRepository->findTemplates();

        return $this->json([
            'templates' => array_map(fn($plan) => $this->serializePlan($plan), $templates)
        ]);
    }

    
    #[Route('/{id}/share', name: 'generate_share', methods: ['POST'])]
    public function generateShareLink(int $id): JsonResponse
    {
        $plan = $this->raidPlanRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Raid plan not found'], Response::HTTP_NOT_FOUND);
        }



        if (!$plan->getShareToken()) {
            $plan->generateShareToken();
        }

        $plan->setIsPublic(true);
        $this->em->flush();

        return $this->json([
            'shareToken' => $plan->getShareToken(),
            'shareUrl' => sprintf('%s/raid-plan/%s',
                $_ENV['FRONT_URL'] ?? 'https://localhost:5173',
                $plan->getShareToken()
            ),
        ]);
    }

    
    #[Route('/public/{shareToken}', name: 'public_view', methods: ['GET'])]
    public function viewPublicPlan(string $shareToken): JsonResponse
    {
        $plan = $this->raidPlanRepository->findOneBy(['shareToken' => $shareToken]);

        if (!$plan || !$plan->isPublic()) {
            return $this->json(['error' => 'Raid plan not found or not public'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializePublicPlan($plan));
    }

    
    #[Route('/{id}/unshare', name: 'revoke_share', methods: ['POST'])]
    public function revokeShare(int $id): JsonResponse
    {
        $plan = $this->raidPlanRepository->find($id);
        if (!$plan) {
            return $this->json(['error' => 'Raid plan not found'], Response::HTTP_NOT_FOUND);
        }



        $plan->setIsPublic(false);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Share link revoked']);
    }

    
    private function serializePlan(RaidPlan $plan): array
    {
        return [
            'id' => $plan->getId(),
            'name' => $plan->getName(),
            'guildId' => $plan->getGuild()->getId(),
            'createdBy' => [
                'id' => $plan->getCreatedBy()->getId(),
                'username' => $plan->getCreatedBy()->getUsername(),
            ],
            'blocks' => $plan->getBlocks(),
            'metadata' => $plan->getMetadata(),
            'isTemplate' => $plan->isTemplate(),
            'bossId' => $plan->getBossId(),
            'raidName' => $plan->getRaidName(),
            'isPublic' => $plan->isPublic(),
            'shareToken' => $plan->getShareToken(),
            'createdAt' => $plan->getCreatedAt()->format('c'),
            'updatedAt' => $plan->getUpdatedAt()->format('c'),
        ];
    }

    
    private function serializePublicPlan(RaidPlan $plan): array
    {
        $blocks = $plan->getBlocks();

        $nameById = [];
        foreach ($plan->getGuild()->getGameCharacters() as $gc) {
            $nameById[$gc->getUuidToString()] = $gc->getName();
        }

        $transform = function (array $block) use (&$transform, $nameById) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? [];

            if ($type === 'ROLE_MATRIX' && isset($data['roleAssignments']) && is_array($data['roleAssignments'])) {
                foreach ($data['roleAssignments'] as $role => $ids) {
                    if (is_array($ids)) {
                        $data['roleAssignments'][$role] = array_map(fn($id) => $nameById[$id] ?? $id, $ids);
                    }
                }
            }

            if ($type === 'GROUPS_GRID' && isset($data['groups']) && is_array($data['groups'])) {
                foreach ($data['groups'] as &$g) {
                    if (isset($g['members']) && is_array($g['members'])) {
                        $g['members'] = array_map(fn($id) => $nameById[$id] ?? $id, $g['members']);
                    }
                }
                unset($g);
            }

            if ($type === 'BOSS_GRID') {
                if (isset($data['assignments']) && is_array($data['assignments'])) {
                    foreach ($data['assignments'] as $posId => $ids) {
                        if (is_array($ids)) {
                            $data['assignments'][$posId] = array_map(fn($id) => $nameById[$id] ?? $id, $ids);
                        }
                    }
                }
            }

            if (in_array($type, ['COOLDOWN_ROTATION', 'INTERRUPT_ROTATION'], true)) {
                if (isset($data['cells']) && is_array($data['cells'])) {
                    foreach ($data['cells'] as $rowId => $cols) {
                        if (!is_array($cols)) continue;
                        foreach ($cols as $colId => $ids) {
                            if (is_array($ids)) {
                                $data['cells'][$rowId][$colId] = array_map(fn($id) => $nameById[$id] ?? $id, $ids);
                            }
                        }
                    }
                }
            }

            $block['data'] = $data;
            return $block;
        };

        $prettyBlocks = [];
        foreach ($blocks as $b) {
            if (is_array($b)) {
                $prettyBlocks[] = $transform($b);
            } else {
                $prettyBlocks[] = $b;
            }
        }

        return [
            'name' => $plan->getName(),
            'guild' => $plan->getGuild()->getName(),
            'blocks' => $prettyBlocks,
            'metadata' => $plan->getMetadata(),
            'bossId' => $plan->getBossId(),
            'raidName' => $plan->getRaidName(),
            'createdAt' => $plan->getCreatedAt()->format('c'),
            'updatedAt' => $plan->getUpdatedAt()->format('c'),
        ];
    }
}
