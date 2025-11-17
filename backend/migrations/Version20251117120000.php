<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add class_spec_secondary to game_character';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_character ADD class_spec_secondary VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game_character DROP COLUMN class_spec_secondary");
    }
}

