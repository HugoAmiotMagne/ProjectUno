<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pending_draw column to game table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD pending_draw INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP COLUMN pending_draw');
    }
}
