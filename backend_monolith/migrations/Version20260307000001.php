<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create note_version table for version history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE note_version (
                id UUID NOT NULL,
                note_id UUID NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT DEFAULT NULL,
                version_number INTEGER NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_note_version_note_id ON note_version (note_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_note_version_note_number ON note_version (note_id, version_number)');

        $this->addSql(<<<'SQL'
            ALTER TABLE note_version
                ADD CONSTRAINT fk_note_version_note
                FOREIGN KEY (note_id) REFERENCES note(id) ON DELETE CASCADE
                NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note_version DROP CONSTRAINT fk_note_version_note');
        $this->addSql('DROP TABLE note_version');
    }
}
