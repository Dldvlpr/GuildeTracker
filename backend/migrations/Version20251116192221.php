<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20251116192221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('CREATE TABLE wow_bosses (id SERIAL NOT NULL, raid_id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, npc_id INT NOT NULL, wowhead_url VARCHAR(500) NOT NULL, order_index INT DEFAULT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A72891E79C55ABC9 ON wow_bosses (raid_id)');
        $this->addSql('CREATE UNIQUE INDEX boss_slug_unique ON wow_bosses (slug)');
        $this->addSql('CREATE TABLE wow_raids (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, expansion VARCHAR(50) NOT NULL, min_level INT DEFAULT NULL, max_players INT DEFAULT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX raid_slug_unique ON wow_raids (slug)');
        $this->addSql('ALTER TABLE wow_bosses ADD CONSTRAINT FK_A72891E79C55ABC9 FOREIGN KEY (raid_id) REFERENCES wow_raids (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE wow_bosses DROP CONSTRAINT FK_A72891E79C55ABC9');
        $this->addSql('DROP TABLE wow_bosses');
        $this->addSql('DROP TABLE wow_raids');
    }
}
