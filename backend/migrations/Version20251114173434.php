<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20251114173434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('CREATE TABLE blizzard_game_realm (id SERIAL NOT NULL, blizzard_realm_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, game_type VARCHAR(255) NOT NULL, region VARCHAR(10) NOT NULL, locale VARCHAR(10) DEFAULT NULL, timezone VARCHAR(50) DEFAULT NULL, connected_realm_id INT DEFAULT NULL, is_tournament BOOLEAN NOT NULL, type VARCHAR(50) DEFAULT NULL, last_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX unique_realm_type_region ON blizzard_game_realm (slug, game_type, region)');
        $this->addSql('COMMENT ON COLUMN blizzard_game_realm.last_sync_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE game_character (id UUID NOT NULL, guild_id UUID DEFAULT NULL, user_player_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, class_spec VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_41DC71365F2131EF ON game_character (guild_id)');
        $this->addSql('CREATE INDEX IDX_41DC71369906783B ON game_character (user_player_id)');
        $this->addSql('COMMENT ON COLUMN game_character.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_character.guild_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_character.user_player_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE game_guild (id UUID NOT NULL, blizzard_realm_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, faction VARCHAR(255) NOT NULL, is_public BOOLEAN NOT NULL, show_dkp_public BOOLEAN NOT NULL, recruiting_status VARCHAR(255) NOT NULL, blizzard_id VARCHAR(255) DEFAULT NULL, realm VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_47730424E46928D3 ON game_guild (blizzard_realm_id)');
        $this->addSql('COMMENT ON COLUMN game_guild.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE guild_invitation (id UUID NOT NULL, guild_id UUID NOT NULL, created_by_id UUID NOT NULL, used_by_id UUID DEFAULT NULL, token VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, role VARCHAR(20) NOT NULL, max_uses INT NOT NULL, used_count INT NOT NULL, is_active BOOLEAN NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_799F3E0D5F37A13B ON guild_invitation (token)');
        $this->addSql('CREATE INDEX IDX_799F3E0D5F2131EF ON guild_invitation (guild_id)');
        $this->addSql('CREATE INDEX IDX_799F3E0DB03A8386 ON guild_invitation (created_by_id)');
        $this->addSql('CREATE INDEX IDX_799F3E0D4C2B72A8 ON guild_invitation (used_by_id)');
        $this->addSql('CREATE INDEX token_idx ON guild_invitation (token)');
        $this->addSql('CREATE INDEX active_idx ON guild_invitation (is_active)');
        $this->addSql('COMMENT ON COLUMN guild_invitation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_invitation.guild_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_invitation.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_invitation.used_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_invitation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN guild_invitation.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN guild_invitation.used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE guild_membership (id UUID NOT NULL, user_id UUID NOT NULL, guild_id UUID NOT NULL, role VARCHAR(20) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E7D8D2AA76ED395 ON guild_membership (user_id)');
        $this->addSql('CREATE INDEX IDX_E7D8D2A5F2131EF ON guild_membership (guild_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_guild ON guild_membership (user_id, guild_id)');
        $this->addSql('COMMENT ON COLUMN guild_membership.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_membership.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_membership.guild_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN guild_membership.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE known_realm_metadata (id SERIAL NOT NULL, slug VARCHAR(255) NOT NULL, region VARCHAR(10) NOT NULL, launch_date DATE DEFAULT NULL, expected_game_type VARCHAR(255) NOT NULL, source VARCHAR(255) DEFAULT NULL, confidence_score SMALLINT DEFAULT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX known_realm_metadata_unique ON known_realm_metadata (slug, region)');
        $this->addSql('COMMENT ON COLUMN known_realm_metadata.launch_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN known_realm_metadata.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE realm_type_override (id SERIAL NOT NULL, slug VARCHAR(255) NOT NULL, region VARCHAR(10) NOT NULL, game_type VARCHAR(255) NOT NULL, reason TEXT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX realm_type_override_unique ON realm_type_override (slug, region)');
        $this->addSql('COMMENT ON COLUMN realm_type_override.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, discord_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, blizzard_id VARCHAR(255) DEFAULT NULL, blizzard_access_token TEXT DEFAULT NULL, blizzard_refresh_token TEXT DEFAULT NULL, blizzard_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_DISCORDID ON "user" (discord_id)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".blizzard_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game_character ADD CONSTRAINT FK_41DC71365F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_character ADD CONSTRAINT FK_41DC71369906783B FOREIGN KEY (user_player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_guild ADD CONSTRAINT FK_47730424E46928D3 FOREIGN KEY (blizzard_realm_id) REFERENCES blizzard_game_realm (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_invitation ADD CONSTRAINT FK_799F3E0D5F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_invitation ADD CONSTRAINT FK_799F3E0DB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_invitation ADD CONSTRAINT FK_799F3E0D4C2B72A8 FOREIGN KEY (used_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_membership ADD CONSTRAINT FK_E7D8D2AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guild_membership ADD CONSTRAINT FK_E7D8D2A5F2131EF FOREIGN KEY (guild_id) REFERENCES game_guild (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game_character DROP CONSTRAINT FK_41DC71365F2131EF');
        $this->addSql('ALTER TABLE game_character DROP CONSTRAINT FK_41DC71369906783B');
        $this->addSql('ALTER TABLE game_guild DROP CONSTRAINT FK_47730424E46928D3');
        $this->addSql('ALTER TABLE guild_invitation DROP CONSTRAINT FK_799F3E0D5F2131EF');
        $this->addSql('ALTER TABLE guild_invitation DROP CONSTRAINT FK_799F3E0DB03A8386');
        $this->addSql('ALTER TABLE guild_invitation DROP CONSTRAINT FK_799F3E0D4C2B72A8');
        $this->addSql('ALTER TABLE guild_membership DROP CONSTRAINT FK_E7D8D2AA76ED395');
        $this->addSql('ALTER TABLE guild_membership DROP CONSTRAINT FK_E7D8D2A5F2131EF');
        $this->addSql('DROP TABLE blizzard_game_realm');
        $this->addSql('DROP TABLE game_character');
        $this->addSql('DROP TABLE game_guild');
        $this->addSql('DROP TABLE guild_invitation');
        $this->addSql('DROP TABLE guild_membership');
        $this->addSql('DROP TABLE known_realm_metadata');
        $this->addSql('DROP TABLE realm_type_override');
        $this->addSql('DROP TABLE "user"');
    }
}
