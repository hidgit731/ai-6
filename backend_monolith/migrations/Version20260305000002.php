<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_favorite and deleted_at columns to note table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note ADD COLUMN is_favorite BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE note ADD COLUMN deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Create indexes for performance
        $this->addSql('CREATE INDEX idx_note_deleted_at ON note (deleted_at) WHERE deleted_at IS NOT NULL');
        $this->addSql('CREATE INDEX idx_note_is_favorite ON note (is_favorite) WHERE is_favorite = true AND deleted_at IS NULL');
        $this->addSql('CREATE INDEX idx_note_folder_deleted ON note (folder_id, deleted_at)');
        $this->addSql('CREATE INDEX idx_note_created_at_deleted ON note (created_at DESC, deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_note_created_at_deleted');
        $this->addSql('DROP INDEX idx_note_folder_deleted');
        $this->addSql('DROP INDEX idx_note_is_favorite');
        $this->addSql('DROP INDEX idx_note_deleted_at');
        $this->addSql('ALTER TABLE note DROP COLUMN deleted_at');
        $this->addSql('ALTER TABLE note DROP COLUMN is_favorite');
    }
}
