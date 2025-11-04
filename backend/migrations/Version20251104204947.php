<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104204947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add BlizzardGameRealm entity with WoW game type support (Retail, Classic, SoD, etc.) and link to GameGuild';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blizzard_game_realm (id SERIAL NOT NULL, blizzard_realm_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, game_type VARCHAR(255) NOT NULL, region VARCHAR(10) NOT NULL, locale VARCHAR(10) DEFAULT NULL, timezone VARCHAR(50) DEFAULT NULL, connected_realm_id INT DEFAULT NULL, is_tournament BOOLEAN NOT NULL, type VARCHAR(50) DEFAULT NULL, last_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX unique_realm_type_region ON blizzard_game_realm (slug, game_type, region)');
        $this->addSql('COMMENT ON COLUMN blizzard_game_realm.last_sync_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game_guild ADD blizzard_realm_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_guild ADD CONSTRAINT FK_47730424E46928D3 FOREIGN KEY (blizzard_realm_id) REFERENCES blizzard_game_realm (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_47730424E46928D3 ON game_guild (blizzard_realm_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game_guild DROP CONSTRAINT FK_47730424E46928D3');
        $this->addSql('DROP TABLE blizzard_game_realm');
        $this->addSql('DROP INDEX IDX_47730424E46928D3');
        $this->addSql('ALTER TABLE game_guild DROP blizzard_realm_id');
    }
}
