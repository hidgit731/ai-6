<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Response\ExportedFile;
use App\Domain\Repository\NoteRepositoryInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class ExportService
{
    public function __construct(
        private readonly NoteRepositoryInterface $noteRepository,
    ) {
    }

    public function exportMarkdown(string $noteId): ExportedFile
    {
        $note = $this->findNote($noteId);

        return ExportedFile::forMarkdown($note->getTitle(), $note->getContent() ?? '');
    }

    public function exportPdf(string $noteId): ExportedFile
    {
        $note = $this->findNote($noteId);

        $content = $note->getContent() ?? '';
        $content = $this->stripWikiLinks($content);

        $converter = new GithubFlavoredMarkdownConverter();
        $html = (string) $converter->convert($content);

        $html = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.6; margin: 2cm; }
                    h1 { font-size: 20pt; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
                    h2 { font-size: 16pt; }
                    h3 { font-size: 14pt; }
                    pre, code { font-family: DejaVu Sans Mono, monospace; background: #f5f5f5; padding: 2px 4px; }
                    pre { padding: 8px; display: block; }
                    blockquote { border-left: 3px solid #ccc; margin-left: 0; padding-left: 12px; color: #555; }
                </style>
            </head>
            <body>
                <h1>{$note->getTitle()}</h1>
                {$html}
            </body>
            </html>
            HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output() ?? '';

        return ExportedFile::forPdf($note->getTitle(), $pdfContent);
    }

    private function findNote(string $noteId): \App\Domain\Entity\Note
    {
        try {
            $uuid = Uuid::fromString($noteId);
        } catch (\InvalidArgumentException) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        $note = $this->noteRepository->findById($uuid);
        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        return $note;
    }

    private function stripWikiLinks(string $content): string
    {
        return preg_replace('/\[\[([^\]]+)\]\]/', '$1', $content) ?? $content;
    }
}
