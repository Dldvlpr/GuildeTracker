<?php

namespace App\Controller;

use App\Entity\RaidPlan;
use App\Entity\GameGuild;
use App\Repository\RaidPlanRepository;
use App\Repository\GameGuildRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\WowClassMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use JsonException;

#[Route('/api/raid-plans', name: 'api_raid_plan_')]
class RaidPlanController extends AbstractController
{
    private const MAX_BLOCK_BYTES = 500000; // ~500 KB
    private const MAX_METADATA_BYTES = 150000; // ~150 KB

    public function __construct(
        private EntityManagerInterface $em,
        private RaidPlanRepository $raidPlanRepository,
        private GameGuildRepository $guildRepository,
        private WowClassMapper $classMapper,
    ) {}

    
    #[Route('/guild/{guildId}', name: 'list', methods: ['GET'])]
    public function list(string $guildId): JsonResponse
    {
        $guild = $this->guildRepository->find($guildId);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GUILD_VIEW', $guild);

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

        $this->denyAccessUnlessGranted('GUILD_VIEW', $plan->getGuild());

        return $this->json($this->serializePlan($plan));
    }

    
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->decodeJsonPayload($request);

        $guildId = $data['guildId'] ?? null;
        if (!$guildId) {
            return $this->json(['error' => 'guildId is required'], Response::HTTP_BAD_REQUEST);
        }

        $guild = $this->guildRepository->find($guildId);
        if (!$guild) {
            return $this->json(['error' => 'Guild not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);

        $this->validatePlanPayload($data);

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

        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $plan->getGuild());

        $data = $this->decodeJsonPayload($request);

        $this->validatePlanPayload($data);

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

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $plan->getGuild());

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

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $plan->getGuild());

        $plan->generateShareToken();
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

        $this->denyAccessUnlessGranted('GUILD_MANAGE', $plan->getGuild());

        $plan->setIsPublic(false);
        $plan->setShareToken(null);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Share link revoked']);
    }

    
    private function decodeJsonPayload(Request $request): array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON payload', $e);
        }

        if (!is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON payload: object expected');
        }

        return $data;
    }

    private function validatePlanPayload(array $data): void
    {
        $blocks = $data['blocks'] ?? [];
        if (!is_array($blocks)) {
            throw new BadRequestHttpException('"blocks" must be an array');
        }

        if (count($blocks) > 200) {
            throw new BadRequestHttpException('Too many blocks (max 200)');
        }

        try {
            $encodedBlocks = json_encode($blocks, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('Invalid blocks payload', $e);
        }

        if (strlen($encodedBlocks) > self::MAX_BLOCK_BYTES) {
            throw new BadRequestHttpException('Blocks payload too large (max ~500 KB)');
        }

        if (array_key_exists('metadata', $data)) {
            if (!is_array($data['metadata']) && !is_null($data['metadata'])) {
                throw new BadRequestHttpException('"metadata" must be an object or null');
            }

            if (is_array($data['metadata'])) {
                try {
                    $encodedMetadata = json_encode($data['metadata'], JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new BadRequestHttpException('Invalid metadata payload', $e);
                }

                if (strlen($encodedMetadata) > self::MAX_METADATA_BYTES) {
                    throw new BadRequestHttpException('Metadata payload too large (max ~150 KB)');
                }
            }
        }
    }

    
    private function serializePlan(RaidPlan $plan): array
    {
        $canManage = $this->isGranted('GUILD_MANAGE', $plan->getGuild());

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
            'shareToken' => $canManage ? $plan->getShareToken() : null,
            'createdAt' => $plan->getCreatedAt()->format('c'),
            'updatedAt' => $plan->getUpdatedAt()->format('c'),
        ];
    }

    
    private function serializePublicPlan(RaidPlan $plan): array
    {
        $blocks = $plan->getBlocks();

        $nameById = [];
        $rosterById = [];
        $rosterByName = [];
        foreach ($plan->getGuild()->getGameCharacters() as $gc) {
            $id = $gc->getUuidToString();
            $name = $gc->getName();
            $nameById[$id] = $name;
            $class = $gc->getClass();
            $color = null;
            if ($class && $class !== 'Unknown') {
                $cid = $this->classMapper->getClassIdByName($class);
                if ($cid) $color = $this->classMapper->getClassColor($cid);
            }
            $entry = [
                'id' => $id,
                'name' => $name,
                'class' => $class,
                'spec' => $gc->getClassSpec(),
                'role' => $gc->getRole(),
                'color' => $color,
            ];
            $rosterById[$id] = $entry;
            $rosterByName[$name] = array_values(array_merge($rosterByName[$name] ?? [], [$entry]));
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
                // Prefer top-level cells if present (editor stores here). Otherwise reconstruct from rows[].cells
                if (isset($data['cells']) && is_array($data['cells'])) {
                    // Map existing top-level cells (IDs -> names)
                    foreach ($data['cells'] as $rowId => $cols) {
                        if (!is_array($cols)) continue;
                        foreach ($cols as $colId => $cell) {
                            if (is_array($cell)) {
                                $isList = array_keys($cell) === range(0, count($cell) - 1);
                                if ($isList) {
                                    $cols[$colId] = array_map(fn($id) => $nameById[$id] ?? $id, $cell);
                                } else {
                                    $from = $cell['from'] ?? null;
                                    $to = $cell['to'] ?? null;
                                    $cols[$colId] = [
                                        'from' => $from ? ($nameById[$from] ?? $from) : null,
                                        'to' => $to ? ($nameById[$to] ?? $to) : null,
                                    ];
                                }
                            } else {
                                $cols[$colId] = $cell ? ($nameById[$cell] ?? $cell) : null;
                            }
                        }
                        $data['cells'][$rowId] = $cols;
                    }
                } elseif (isset($data['rows']) && is_array($data['rows'])) {
                    // Reconstruct from rows[].cells (legacy)
                    $cells = [];
                    foreach ($data['rows'] as $row) {
                        $rowId = $row['id'] ?? null;
                        if (!$rowId) continue;
                        $rowCells = $row['cells'] ?? [];
                        if (!is_array($rowCells)) $rowCells = [];
                        $mapped = [];
                        foreach ($rowCells as $colId => $cell) {
                            if (is_array($cell)) {
                                $isList = array_keys($cell) === range(0, count($cell) - 1);
                                if ($isList) {
                                    $mapped[$colId] = array_map(fn($id) => $nameById[$id] ?? $id, $cell);
                                } else {
                                    $from = $cell['from'] ?? null;
                                    $to = $cell['to'] ?? null;
                                    $mapped[$colId] = [
                                        'from' => $from ? ($nameById[$from] ?? $from) : null,
                                        'to' => $to ? ($nameById[$to] ?? $to) : null,
                                    ];
                                }
                            } else {
                                // single value cell
                                $mapped[$colId] = $cell ? ($nameById[$cell] ?? $cell) : null;
                            }
                        }
                        $cells[$rowId] = $mapped;
                    }
                    $data['cells'] = $cells;
                }
            }

            if ($type === 'BENCH_ROSTER' && isset($data['bench']) && is_array($data['bench'])) {
                $data['bench'] = array_map(fn($id) => $nameById[$id] ?? $id, $data['bench']);
            }

            if ($type === 'FREE_CANVAS' && isset($data['shapes']) && is_array($data['shapes'])) {
                foreach ($data['shapes'] as &$shape) {
                    if (($shape['type'] ?? null) === 'player') {
                        $charId = $shape['characterId'] ?? null;
                        if ($charId && isset($nameById[$charId])) {
                            $shape['playerName'] = $nameById[$charId];
                        }
                        unset($shape['characterId']);
                    }
                }
                unset($shape);
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
            // Prefer roster keyed by ID, also expose name-keyed list for compatibility
            'roster' => $rosterById,
            'rosterByName' => $rosterByName,
        ];
    }
}
