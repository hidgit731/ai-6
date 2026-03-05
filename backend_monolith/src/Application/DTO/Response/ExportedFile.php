<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

final readonly class ExportedFile
{
    public function __construct(
        public string $filename,
        public string $content,
        public string $mimeType,
    ) {
    }

    public static function forMarkdown(string $title, string $content): self
    {
        return new self(
            filename: self::sanitizeFilename($title).'.md',
            content: "# {$title}\n\n{$content}",
            mimeType: 'text/markdown; charset=UTF-8',
        );
    }

    public static function forPdf(string $title, string $content): self
    {
        return new self(
            filename: self::sanitizeFilename($title).'.pdf',
            content: $content,
            mimeType: 'application/pdf',
        );
    }

    private static function sanitizeFilename(string $title): string
    {
        $sanitized = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F]+/', '-', $title) ?? '-';
        $sanitized = preg_replace('/-{2,}/', '-', $sanitized) ?? '-';
        $sanitized = trim($sanitized, '-');
        $sanitized = mb_substr($sanitized, 0, 200);

        return '' !== $sanitized ? $sanitized : 'note';
    }
}
