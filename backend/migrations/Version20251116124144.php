<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20251116124144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE raid_plans ADD share_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE raid_plans ADD is_public BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E4EEAEBCD6594DD6 ON raid_plans (share_token)');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_E4EEAEBCD6594DD6');
        $this->addSql('ALTER TABLE raid_plans DROP share_token');
        $this->addSql('ALTER TABLE raid_plans DROP is_public');
    }
}
