# Exemples d'utilisation - BlizzardGameRealm

## Accéder aux informations du realm d'une guilde

### Dans un controller

```php
use App\Entity\GameGuild;
use Doctrine\ORM\EntityManagerInterface;

class GuildController extends AbstractController
{
    #[Route('/guild/{id}', name: 'guild_show')]
    public function show(GameGuild $guild): Response
    {
        $realm = $guild->getBlizzardRealm();

        if (!$realm) {
            throw $this->createNotFoundException('Guild realm not configured');
        }

        return $this->render('guild/show.html.twig', [
            'guild' => $guild,
            'realm_name' => $realm->getName(),
            'game_type' => $realm->getGameType()->getLabel(),
            'region' => strtoupper($realm->getRegion()),
        ]);
    }
}
```

### Dans un template Twig

```twig
{# guild/show.html.twig #}
<h1>{{ guild.name }}</h1>

{% if guild.blizzardRealm %}
    <p>
        <strong>Server:</strong>
        {{ guild.blizzardRealm.name }}
        ({{ guild.blizzardRealm.gameType.label }})
        - {{ guild.blizzardRealm.region|upper }}
    </p>

    <p>
        <strong>Type:</strong>
        {{ guild.blizzardRealm.type ?? 'N/A' }}
    </p>
{% else %}
    <p class="warning">Server information not available</p>
{% endif %}
```

## Appeler l'API Blizzard avec les namespaces automatiques

### Service amélioré

```php
use App\Entity\GameGuild;
use App\Service\BlizzardService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GuildSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private BlizzardService $blizzardService,
    ) {}

    public function syncGuildRoster(GameGuild $guild, string $accessToken): array
    {
        $realm = $guild->getBlizzardRealm();

        if (!$realm) {
            throw new \RuntimeException('Guild has no realm configured');
        }

        // Namespace calculé automatiquement selon le type de jeu
        $namespace = $realm->getDynamicNamespace();
        $region = $realm->getRegion();

        // Construction de l'URL
        $base = sprintf('https://%s.api.blizzard.com', $region);
        $url = sprintf(
            '%s/data/wow/guild/%s/%s/roster?namespace=%s&locale=en_US',
            $base,
            $realm->getSlug(),
            strtolower($guild->getName()),
            $namespace
        );

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);

        return $response->toArray();
    }

    public function getGuildDetails(GameGuild $guild, string $accessToken): array
    {
        $realm = $guild->getBlizzardRealm();

        // Profile namespace pour les guildes Classic
        $namespace = $realm->getProfileNamespace();
        $region = $realm->getRegion();

        $base = sprintf('https://%s.api.blizzard.com', $region);
        $url = sprintf(
            '%s/data/wow/guild/%s/%s?namespace=%s&locale=en_US',
            $base,
            $realm->getSlug(),
            strtolower($guild->getName()),
            $namespace
        );

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);

        return $response->toArray();
    }
}
```

## Rechercher des guildes par type de jeu

### Repository personnalisé

```php
// src/Repository/GameGuildRepository.php

use App\Entity\GameGuild;
use App\Enum\WowGameType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameGuildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameGuild::class);
    }

    /**
     * Trouver toutes les guildes d'un type de jeu spécifique
     */
    public function findByGameType(WowGameType $gameType, string $region = 'eu'): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.blizzardRealm', 'r')
            ->where('r.gameType = :gameType')
            ->andWhere('r.region = :region')
            ->setParameter('gameType', $gameType)
            ->setParameter('region', $region)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver une guilde par nom et realm slug
     */
    public function findByNameAndRealm(
        string $guildName,
        string $realmSlug,
        WowGameType $gameType,
        string $region = 'eu'
    ): ?GameGuild {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.blizzardRealm', 'r')
            ->where('LOWER(g.name) = :name')
            ->andWhere('r.slug = :slug')
            ->andWhere('r.gameType = :gameType')
            ->andWhere('r.region = :region')
            ->setParameter('name', strtolower($guildName))
            ->setParameter('slug', $realmSlug)
            ->setParameter('gameType', $gameType)
            ->setParameter('region', $region)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compter les guildes par type de jeu
     */
    public function countByGameType(): array
    {
        $results = $this->createQueryBuilder('g')
            ->select('r.gameType', 'COUNT(g.id) as total')
            ->innerJoin('g.blizzardRealm', 'r')
            ->groupBy('r.gameType')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $row) {
            $gameType = WowGameType::from($row['gameType']);
            $stats[$gameType->getLabel()] = (int)$row['total'];
        }

        return $stats;
    }

    /**
     * Guildes publiques d'un realm spécifique
     */
    public function findPublicGuildsByRealm(int $realmId): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.blizzardRealm = :realmId')
            ->andWhere('g.isPublic = true')
            ->setParameter('realmId', $realmId)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

### Utilisation dans un controller

```php
use App\Enum\WowGameType;
use App\Repository\GameGuildRepository;

class GuildBrowserController extends AbstractController
{
    #[Route('/guilds/classic-anniversary', name: 'guilds_classic')]
    public function classicGuilds(GameGuildRepository $guildRepository): Response
    {
        $guilds = $guildRepository->findByGameType(
            WowGameType::CLASSIC_ANNIVERSARY,
            'eu'
        );

        return $this->render('guild/list.html.twig', [
            'guilds' => $guilds,
            'title' => 'Classic Anniversary Guilds'
        ]);
    }

    #[Route('/guilds/stats', name: 'guilds_stats')]
    public function stats(GameGuildRepository $guildRepository): Response
    {
        $stats = $guildRepository->countByGameType();

        return $this->render('guild/stats.html.twig', [
            'stats' => $stats
        ]);
    }
}
```

## Créer une nouvelle guilde depuis l'API

### Form avec sélection de realm

```php
// src/Form/GuildCreateType.php

use App\Entity\BlizzardGameRealm;
use App\Entity\GameGuild;
use App\Enum\WowGameType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuildCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('faction', ChoiceType::class, [
                'choices' => [
                    'Alliance' => 'Alliance',
                    'Horde' => 'Horde',
                ],
            ])
            ->add('gameType', EnumType::class, [
                'class' => WowGameType::class,
                'label' => 'Game Type',
            ])
            ->add('blizzardRealm', EntityType::class, [
                'class' => BlizzardGameRealm::class,
                'choice_label' => function (BlizzardGameRealm $realm) {
                    return sprintf(
                        '%s (%s - %s)',
                        $realm->getName(),
                        $realm->getGameType()->getLabel(),
                        strtoupper($realm->getRegion())
                    );
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.region = :region')
                        ->setParameter('region', 'eu')
                        ->orderBy('r.name', 'ASC');
                },
            ]);
    }
}
```

### Controller pour créer une guilde

```php
use App\Entity\GameGuild;
use App\Form\GuildCreateType;

class GuildManagementController extends AbstractController
{
    #[Route('/guild/create', name: 'guild_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $guild = new GameGuild();
        $form = $this->createForm(GuildCreateType::class, $guild);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($guild);
            $em->flush();

            $this->addFlash('success', sprintf(
                'Guild "%s" created on %s (%s)',
                $guild->getName(),
                $guild->getBlizzardRealm()->getName(),
                $guild->getBlizzardRealm()->getGameType()->getLabel()
            ));

            return $this->redirectToRoute('guild_show', ['id' => $guild->getId()]);
        }

        return $this->render('guild/create.html.twig', [
            'form' => $form,
        ]);
    }
}
```

## API REST endpoint

### Retourner les informations de realm dans l'API

```php
// src/Controller/Api/GuildApiController.php

use App\Entity\GameGuild;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/guilds')]
class GuildApiController extends AbstractController
{
    #[Route('/{id}', name: 'api_guild_show', methods: ['GET'])]
    public function show(GameGuild $guild): JsonResponse
    {
        $realm = $guild->getBlizzardRealm();

        return $this->json([
            'id' => $guild->getId()->toString(),
            'name' => $guild->getName(),
            'faction' => $guild->getFaction(),
            'realm' => $realm ? [
                'id' => $realm->getId(),
                'name' => $realm->getName(),
                'slug' => $realm->getSlug(),
                'game_type' => [
                    'value' => $realm->getGameType()->value,
                    'label' => $realm->getGameType()->getLabel(),
                ],
                'region' => $realm->getRegion(),
                'type' => $realm->getType(),
            ] : null,
            'is_public' => $guild->isPublic(),
            'recruiting_status' => $guild->getRecruitingStatus()->value,
        ]);
    }

    #[Route('', name: 'api_guild_list', methods: ['GET'])]
    public function list(
        Request $request,
        GameGuildRepository $guildRepository
    ): JsonResponse {
        $gameType = $request->query->get('game_type');
        $region = $request->query->get('region', 'eu');

        if ($gameType) {
            $guilds = $guildRepository->findByGameType(
                WowGameType::from($gameType),
                $region
            );
        } else {
            $guilds = $guildRepository->findAll();
        }

        return $this->json([
            'guilds' => array_map(function (GameGuild $guild) {
                $realm = $guild->getBlizzardRealm();
                return [
                    'id' => $guild->getId()->toString(),
                    'name' => $guild->getName(),
                    'realm' => $realm ? $realm->getName() : null,
                    'game_type' => $realm ? $realm->getGameType()->getLabel() : null,
                ];
            }, $guilds)
        ]);
    }
}
```

## Tests unitaires

```php
// tests/Entity/BlizzardGameRealmTest.php

use App\Entity\BlizzardGameRealm;
use App\Enum\WowGameType;
use PHPUnit\Framework\TestCase;

class BlizzardGameRealmTest extends TestCase
{
    public function testGetProfileNamespace(): void
    {
        $realm = new BlizzardGameRealm();
        $realm->setGameType(WowGameType::CLASSIC_ANNIVERSARY);
        $realm->setRegion('eu');

        $this->assertEquals('profile-classic1x-eu', $realm->getProfileNamespace());
    }

    public function testGetDynamicNamespace(): void
    {
        $realm = new BlizzardGameRealm();
        $realm->setGameType(WowGameType::RETAIL);
        $realm->setRegion('us');

        $this->assertEquals('dynamic-us', $realm->getDynamicNamespace());
    }

    public function testToString(): void
    {
        $realm = new BlizzardGameRealm();
        $realm->setName('Sulfuron');
        $realm->setGameType(WowGameType::SEASON_OF_DISCOVERY);
        $realm->setRegion('eu');

        $this->assertEquals('Sulfuron (Season of Discovery - EU)', (string)$realm);
    }
}
```
