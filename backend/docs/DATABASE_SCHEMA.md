# Schéma de base de données - GuildeTracker

## Diagramme des relations

```
┌─────────────────────────────────┐
│      BlizzardGameRealm          │
├─────────────────────────────────┤
│ PK  id                          │
│     blizzard_realm_id           │
│     name                        │
│     slug                        │
│     game_type (ENUM)            │  ← WowGameType
│     region                      │     - RETAIL
│     locale                      │     - CLASSIC_ERA
│     timezone                    │     - CLASSIC_PROGRESSION
│     connected_realm_id          │     - CLASSIC_ANNIVERSARY
│     is_tournament               │     - SEASON_OF_DISCOVERY
│     type                        │     - HARDCORE
│     last_sync_at                │
└────────┬────────────────────────┘
         │
         │ 1:N
         │
         ↓
┌────────────────────────────────────────┐
│          GameGuild                     │
├────────────────────────────────────────┤
│ PK  id (UUID)                          │
│     name                               │
│     faction                            │
│     is_public                          │
│     show_dkp_public                    │
│     recruiting_status (ENUM)           │
│ FK  blizzard_realm_id                  │ → BlizzardGameRealm
│     @deprecated realm                  │
│     @deprecated blizzard_id            │
└────────┬───────────────────────────────┘
         │
         │ 1:N
         │
         ↓
┌────────────────────────────────────────┐
│        GameCharacter                   │
├────────────────────────────────────────┤
│ PK  id (UUID)                          │
│     name                               │
│     class                              │
│     class_spec                         │
│     role                               │
│ FK  guild_id                           │ → GameGuild
│ FK  user_player_id                     │ → User
└────────────────────────────────────────┘


┌────────────────────────────────────────┐
│             User                       │
├────────────────────────────────────────┤
│ PK  id                                 │
│     email                              │
│     roles (JSON)                       │
│     discord_id                         │
│     blizzard_access_token              │
│     blizzard_refresh_token             │
│     blizzard_token_expires_at          │
└────────┬───────────────────────────────┘
         │
         │ N:M (via GuildMembership)
         │
         ↓
┌────────────────────────────────────────┐
│       GuildMembership                  │
├────────────────────────────────────────┤
│ PK  id (UUID)                          │
│ FK  user_id                            │ → User
│ FK  guild_id                           │ → GameGuild
│     role (ENUM)                        │ ← GuildRole
│     joined_at                          │     - LEADER
│     left_at                            │     - OFFICER
│     is_active                          │     - MEMBER
└────────────────────────────────────────┘


┌────────────────────────────────────────┐
│       GuildInvitation                  │
├────────────────────────────────────────┤
│ PK  id (UUID)                          │
│ FK  guild_id                           │ → GameGuild
│     invited_email                      │
│     token                              │
│     status                             │
│     expires_at                         │
│     created_at                         │
└────────────────────────────────────────┘
```

## Relations clés

### BlizzardGameRealm → GameGuild (1:N)
- Un realm peut avoir plusieurs guildes
- Une guilde appartient à un seul realm
- Permet de savoir sur quel serveur et quel type de jeu est la guilde

### GameGuild → GameCharacter (1:N)
- Une guilde a plusieurs personnages
- Un personnage appartient à une seule guilde

### User → GameCharacter (1:N)
- Un utilisateur peut avoir plusieurs personnages
- Un personnage appartient à un seul utilisateur (optionnel)

### User ↔ GameGuild via GuildMembership (N:M)
- Un utilisateur peut être membre de plusieurs guildes
- Une guilde a plusieurs membres
- GuildMembership stocke le rôle (Leader, Officer, Member)

## Index importants

```sql
-- Unicité des realms
UNIQUE INDEX unique_realm_type_region ON blizzard_game_realm (slug, game_type, region);

-- Performance des requêtes
INDEX ON game_guild (blizzard_realm_id);
INDEX ON game_character (guild_id);
INDEX ON game_character (user_player_id);
INDEX ON guild_membership (user_id, guild_id);
```

## Types ENUM utilisés

### WowGameType (blizzard_game_realm.game_type)
```
- retail
- classic-era
- classic-progression
- classic-anniversary
- season-of-discovery
- hardcore
```

### RecruitingStatus (game_guild.recruiting_status)
```
- OPEN
- CLOSED
- SELECTIVE
```

### GuildRole (guild_membership.role)
```
- LEADER
- OFFICER
- MEMBER
```

## Migration des données

Les champs `realm` et `blizzard_id` dans `GameGuild` sont deprecated mais conservés pour compatibilité:

```php
// Ancienne approche (deprecated)
$guild->setRealm('Sulfuron');
$guild->setBlizzardId('some-id');

// Nouvelle approche (recommandée)
$realm = $realmRepository->findOneBy([
    'slug' => 'sulfuron',
    'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
    'region' => 'eu'
]);
$guild->setBlizzardRealm($realm);
```
