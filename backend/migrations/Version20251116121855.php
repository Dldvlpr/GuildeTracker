<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20251116121855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('CREATE TABLE raid_plans (id SERIAL NOT NULL, guild_id UUID NOT NULL, created_by_id UUID NOT NULL, name VARCHAR(255) NOT NULL, blocks JSON NOT NULL, metadata JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_template BOOLEAN DEFAULT false NOT NULL, boss_id VARCHAR(50) DEFAULT NULL, raid_name VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E4EEAEBC5F2131EF ON raid_plans (guild_id)');
        $this->addSql('CREATE INDEX IDX_E4EEAEBCB03A8386 ON raid_plans (created_by_id)');
        $this->addSql('COMMENT ON COLUMN raid_plans.guild_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN raid_plans.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE raid_plans ADD CONSTRAINT FK_E4EEAEBC5F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE raid_plans ADD CONSTRAINT FK_E4EEAEBCB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE raid_plans DROP CONSTRAINT FK_E4EEAEBC5F2131EF');
        $this->addSql('ALTER TABLE raid_plans DROP CONSTRAINT FK_E4EEAEBCB03A8386');
        $this->addSql('DROP TABLE raid_plans');
    }
}
