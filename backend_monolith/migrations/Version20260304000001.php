<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add folder table and folder_id to note';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE folder (id UUID NOT NULL, parent_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_folder_parent_id ON folder (parent_id)');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT fk_folder_parent FOREIGN KEY (parent_id) REFERENCES folder (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT uq_folder_parent_name UNIQUE NULLS NOT DISTINCT (parent_id, name)');
        $this->addSql('ALTER TABLE note ADD COLUMN folder_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_note_folder_id ON note (folder_id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT fk_note_folder FOREIGN KEY (folder_id) REFERENCES folder (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP CONSTRAINT fk_note_folder');
        $this->addSql('DROP INDEX idx_note_folder_id');
        $this->addSql('ALTER TABLE note DROP COLUMN folder_id');
        $this->addSql('ALTER TABLE folder DROP CONSTRAINT fk_folder_parent');
        $this->addSql('DROP TABLE folder');
    }
}
