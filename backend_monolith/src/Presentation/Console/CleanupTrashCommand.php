<?php

declare(strict_types=1);

namespace App\Presentation\Console;

use App\Application\Service\NoteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:cleanup-trash')]
final class CleanupTrashCommand extends Command
{
    public function __construct(private readonly NoteService $noteService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->noteService->emptyTrash();
        $output->writeln("Deleted {$count} expired notes from trash.");

        return Command::SUCCESS;
    }
}
