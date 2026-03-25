<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325112053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card (id INT AUTO_INCREMENT NOT NULL, color VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, player_id INT DEFAULT NULL, INDEX IDX_161498D399E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, direction VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, current_player_id INT DEFAULT NULL, top_card_id INT DEFAULT NULL, INDEX IDX_232B318C42C04473 (current_player_id), INDEX IDX_232B318CAE95A881 (top_card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_bot TINYINT NOT NULL, game_id INT DEFAULT NULL, INDEX IDX_98197A65E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D399E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C42C04473 FOREIGN KEY (current_player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CAE95A881 FOREIGN KEY (top_card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D399E6F5DF');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C42C04473');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CAE95A881');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65E48FD905');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
