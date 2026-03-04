<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add full-text search vector to note table (tsvector column, GIN index, trigger)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note ADD COLUMN search_vector tsvector');

        // GIN index for fast full-text search lookups
        $this->addSql('CREATE INDEX idx_note_search_vector ON note USING GIN(search_vector)');

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION note_search_vector_update() RETURNS trigger AS $$
            BEGIN
              NEW.search_vector :=
                setweight(to_tsvector('russian', coalesce(NEW.title, '')), 'A') ||
                setweight(to_tsvector('russian', coalesce(NEW.content, '')), 'B');
              RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TRIGGER trg_note_search_vector
              BEFORE INSERT OR UPDATE ON note
              FOR EACH ROW EXECUTE FUNCTION note_search_vector_update()
        SQL);

        // Reindex all existing rows by firing the trigger
        $this->addSql('UPDATE note SET updated_at = updated_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS trg_note_search_vector ON note');
        $this->addSql('DROP FUNCTION IF EXISTS note_search_vector_update()');
        $this->addSql('DROP INDEX IF EXISTS idx_note_search_vector');
        $this->addSql('ALTER TABLE note DROP COLUMN search_vector');
    }
}
