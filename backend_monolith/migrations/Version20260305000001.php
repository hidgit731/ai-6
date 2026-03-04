<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tag and note_tag tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tag (id UUID NOT NULL, name VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uq_tag_name ON tag (name)');
        $this->addSql('CREATE TABLE note_tag (note_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(note_id, tag_id))');
        $this->addSql('CREATE INDEX idx_note_tag_tag_id ON note_tag (tag_id)');
        $this->addSql('ALTER TABLE note_tag ADD CONSTRAINT fk_note_tag_note FOREIGN KEY (note_id) REFERENCES note(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE note_tag ADD CONSTRAINT fk_note_tag_tag FOREIGN KEY (tag_id) REFERENCES tag(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note_tag DROP CONSTRAINT fk_note_tag_note');
        $this->addSql('ALTER TABLE note_tag DROP CONSTRAINT fk_note_tag_tag');
        $this->addSql('DROP TABLE note_tag');
        $this->addSql('DROP TABLE tag');
    }
}
