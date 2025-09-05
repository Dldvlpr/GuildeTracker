<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905180204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_character (id SERIAL NOT NULL, guild_id INT DEFAULT NULL, user_player_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, class_spec VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_41DC71365F2131EF ON game_character (guild_id)');
        $this->addSql('CREATE INDEX IDX_41DC71369906783B ON game_character (user_player_id)');
        $this->addSql('COMMENT ON COLUMN game_character.user_player_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE game_guild (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, faction VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, discord_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, blizzard_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_DISCORDID ON "user" (discord_id)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE game_character ADD CONSTRAINT FK_41DC71365F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_character ADD CONSTRAINT FK_41DC71369906783B FOREIGN KEY (user_player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game_character DROP CONSTRAINT FK_41DC71365F2131EF');
        $this->addSql('ALTER TABLE game_character DROP CONSTRAINT FK_41DC71369906783B');
        $this->addSql('DROP TABLE game_character');
        $this->addSql('DROP TABLE game_guild');
        $this->addSql('DROP TABLE "user"');
    }
}
