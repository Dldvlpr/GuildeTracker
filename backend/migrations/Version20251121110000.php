<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create spell table to store Blizzard spell metadata (icon file, name, sync date)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE spell (id UUID NOT NULL, blizzard_id INT NOT NULL, name VARCHAR(255) NOT NULL, icon_file VARCHAR(255) DEFAULT NULL, last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8F6C877A66F05D ON spell (blizzard_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE spell');
    }
}
