<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907214531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_character (id UUID NOT NULL, guild_id UUID DEFAULT NULL, user_player_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, class_spec VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_41DC71365F2131EF ON game_character (guild_id)');
        $this->addSql('CREATE INDEX IDX_41DC71369906783B ON game_character (user_player_id)');
        $this->addSql('COMMENT ON COLUMN game_character.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_character.guild_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_character.user_player_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE game_guild (id UUID NOT NULL, name VARCHAR(255) NOT NULL, faction VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN game_guild.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE guild_membership (id UUID NOT NULL, user_id UUID NOT NULL, guild_id UUID NOT NULL, role VARCHAR(20) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E7D8D2AA76ED395 ON guild_membership (user_id)');
        $this->addSql('CREATE INDEX IDX_E7D8D2A5F2131EF ON guild_membership (guild_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_guild ON guild_membership (user_id, guild_id)');
        $this->addSql('COMMENT ON COLUMN guild_membership.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_membership.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_membership.guild_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_membership.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, discord_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, blizzard_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_DISCORDID ON "user" (discord_id)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE game_character ADD CONSTRAINT FK_41DC71365F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_character ADD CONSTRAINT FK_41DC71369906783B FOREIGN KEY (user_player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_membership ADD CONSTRAINT FK_E7D8D2AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_membership ADD CONSTRAINT FK_E7D8D2A5F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game_character DROP CONSTRAINT FK_41DC71365F2131EF');
        $this->addSql('ALTER TABLE game_character DROP CONSTRAINT FK_41DC71369906783B');
        $this->addSql('ALTER TABLE guild_membership DROP CONSTRAINT FK_E7D8D2AA76ED395');
        $this->addSql('ALTER TABLE guild_membership DROP CONSTRAINT FK_E7D8D2A5F2131EF');
        $this->addSql('DROP TABLE game_character');
        $this->addSql('DROP TABLE game_guild');
        $this->addSql('DROP TABLE guild_membership');
        $this->addSql('DROP TABLE "user"');
    }
}
