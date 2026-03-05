<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260308000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create note_link table for wiki-link connections between notes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE note_link (
            id UUID NOT NULL,
            source_note_id UUID NOT NULL,
            target_note_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('ALTER TABLE note_link ADD CONSTRAINT fk_note_link_source FOREIGN KEY (source_note_id) REFERENCES note (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE note_link ADD CONSTRAINT fk_note_link_target FOREIGN KEY (target_note_id) REFERENCES note (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE note_link ADD CONSTRAINT uq_note_link_source_target UNIQUE (source_note_id, target_note_id)');
        $this->addSql('CREATE INDEX idx_note_link_source_note_id ON note_link (source_note_id)');
        $this->addSql('CREATE INDEX idx_note_link_target_note_id ON note_link (target_note_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS note_link');
    }
}
