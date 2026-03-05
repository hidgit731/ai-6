<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Folder;
use App\Domain\Entity\Note;
use App\Domain\Entity\NoteLink;
use App\Domain\Entity\NoteVersion;
use App\Domain\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(private readonly string $appEnv)
    {
    }

    public function load(ObjectManager $manager): void
    {
        if ('prod' === $this->appEnv) {
            throw new \RuntimeException('Cannot load fixtures in production environment.');
        }

        // ── Tags ──────────────────────────────────────────────────────────────
        $tagNames = ['rust', 'php', 'python', 'algorithms', 'books', 'ideas', 'todo',
            'journal', 'research', 'tools', 'productivity', 'math', 'linux', 'web', 'ai'];
        $tags = [];
        foreach ($tagNames as $name) {
            $tag = new Tag($name);
            $manager->persist($tag);
            $tags[$name] = $tag;
        }
        $manager->flush();

        // ── Folders ───────────────────────────────────────────────────────────
        $technology = new Folder('Technology');
        $science = new Folder('Science');
        $personal = new Folder('Personal');
        $programming = new Folder('Programming', $technology);
        $physics = new Folder('Physics', $science);

        foreach ([$technology, $science, $personal, $programming, $physics] as $folder) {
            $manager->persist($folder);
        }
        $manager->flush();

        // ── Notes ─────────────────────────────────────────────────────────────
        $notesData = [
            // Programming folder
            ['Rust Ownership Model', "## Introduction\n\nRust's ownership system is unique.\n\n- Each value has an owner\n- Only one owner at a time\n- Value dropped when owner goes out of scope\n\n```rust\nlet s = String::from(\"hello\");\nlet t = s; // s is moved\n```\n\nSee [[Memory Safety in Rust]] for details.", $programming, ['rust', 'research']],
            ['Memory Safety in Rust', "# Memory Safety\n\nRust prevents data races at compile time.\n\n```rust\nfn main() {\n    let mut v = vec![1, 2, 3];\n    let first = &v[0];\n    v.push(4); // compile error!\n    println!(\"{}\", first);\n}\n```", $programming, ['rust', 'research']],
            ['PHP 8.4 New Features', "## PHP 8.4 Features\n\n- Property hooks\n- Asymmetric visibility\n- Array functions improvements\n\nSee [[Symfony Best Practices]] for usage.", $programming, ['php', 'web']],
            ['Symfony Best Practices', "# Symfony Best Practices\n\n1. Use invokable controllers\n2. Thin controllers, fat services\n3. Repository pattern for all entities\n\nRelated: [[PHP 8.4 New Features]]", $programming, ['php', 'web']],
            ['Python Asyncio Guide', "## Asyncio\n\nAsync programming in Python.\n\n```python\nasync def main():\n    await asyncio.sleep(1)\n    print('done')\n```\n\nSee [[Python Type Hints]].", $programming, ['python', 'research']],
            ['Python Type Hints', "# Type Hints in Python 3.12\n\n```python\nfrom typing import TypeVar, Generic\n\nT = TypeVar('T')\n\nclass Stack(Generic[T]):\n    def push(self, item: T) -> None: ...\n```", $programming, ['python']],
            ['Algorithm Complexity', "## Big O Notation\n\n| Algorithm | Best | Average | Worst |\n|-----------|------|---------|-------|\n| Quicksort | O(n log n) | O(n log n) | O(n\xc2\xb2) |\n| Mergesort | O(n log n) | O(n log n) | O(n log n) |\n\nSee [[Graph Algorithms]] for advanced topics.", $programming, ['algorithms', 'math']],
            ['Graph Algorithms', "# Graph Algorithms\n\n## Dijkstra's Algorithm\n\nFinds shortest path in a weighted graph.\n\n```python\ndef dijkstra(graph, start):\n    dist = {v: float('inf') for v in graph}\n    dist[start] = 0\n    # ...\n```\n\nRelated: [[Algorithm Complexity]]", $programming, ['algorithms', 'math']],
            ['Linux Command Cheatsheet', "# Essential Linux Commands\n\n```bash\n# File operations\nls -la\nfind . -name '*.php' -type f\n\n# Process management\nps aux | grep nginx\nkill -9 PID\n\n# Network\nnetstat -tulpn\ncurl -I https://example.com\n```", $programming, ['linux', 'tools']],
            ['Docker Best Practices', "## Docker Tips\n\n1. Use multi-stage builds\n2. Non-root users\n3. Minimal base images (Alpine)\n\n```dockerfile\nFROM php:8.4-fpm-alpine AS base\nRUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app\n```", $programming, ['linux', 'tools']],

            // Technology folder
            ['AI Tools Overview', "# AI Development Tools\n\nModern AI tools for developers:\n\n- **Claude** \u2014 reasoning and code\n- **Cursor** \u2014 AI-powered IDE\n- **GitHub Copilot** \u2014 code completion\n\nSee [[Machine Learning Basics]].", $technology, ['ai', 'tools']],
            ['Machine Learning Basics', "## ML Fundamentals\n\n### Supervised Learning\nTrain on labeled data.\n\n### Unsupervised Learning\nFind patterns without labels.\n\n### Key metrics\n- Accuracy, Precision, Recall, F1\n\nRelated: [[AI Tools Overview]]", $technology, ['ai', 'research']],
            ['Web Performance Tips', "# Web Performance\n\n## Core Web Vitals\n- LCP < 2.5s\n- FID < 100ms\n- CLS < 0.1\n\n## Techniques\n1. Lazy loading images\n2. Code splitting\n3. CDN for static assets", $technology, ['web', 'tools']],

            // Science folder
            ['Quantum Mechanics Introduction', "# Quantum Mechanics\n\nThe wave function \xce\xa8 describes the quantum state.\n\nKey concepts:\n- Superposition\n- Entanglement\n- Wave-particle duality\n\nSee [[Quantum Computing Basics]].", $physics, ['math', 'research']],
            ['Quantum Computing Basics', "## Quantum Computing\n\nQubits can be in superposition of 0 and 1.\n\n### Quantum Gates\n- Hadamard (H)\n- CNOT\n- Toffoli\n\nRelated: [[Quantum Mechanics Introduction]]", $physics, ['math', 'research', 'ai']],
            ['Thermodynamics Laws', "# Laws of Thermodynamics\n\n1. **First Law**: Energy conservation\n2. **Second Law**: Entropy increases\n3. **Third Law**: Absolute zero", $physics, ['math']],

            // Personal folder
            ['2026 Reading List', "# Books to Read in 2026\n\n## Technical\n- [ ] Designing Data-Intensive Applications\n- [ ] Clean Architecture\n- [x] The Pragmatic Programmer\n\n## Non-technical\n- [ ] Thinking, Fast and Slow\n- [ ] Sapiens", $personal, ['books', 'todo']],
            ['Weekly Review Template', "# Weekly Review\n\n## Wins this week\n-\n\n## Challenges\n-\n\n## Next week priorities\n1.\n2.\n3.", $personal, ['journal', 'productivity']],
            ['Productivity System', "# My Productivity Setup\n\n## Morning Routine\n- 6:00 Exercise\n- 7:00 Review goals\n- 7:30 Deep work block\n\n## Tools\n- \u0417\u0430\u043c\u0435\u0442\u043a\u0438 (this app!)\n- Calendar blocking\n- Pomodoro timer", $personal, ['productivity', 'tools']],
            ['Ideas Backlog', "# Random Ideas\n\n## App Ideas\n- [ ] Recipe manager with nutrition tracking\n- [ ] Habit tracker with streaks\n- [ ] Code snippet manager\n\n## Business Ideas\n- [ ] Developer productivity SaaS", $personal, ['ideas', 'todo']],

            // No folder (root level)
            ['Getting Started', "# Welcome to \u0417\u0430\u043c\u0435\u0442\u043a\u0438!\n\nThis is your note-taking application.\n\n## Features\n- Markdown editor with preview\n- Folders for organisation\n- Tags for categorisation\n- Full-text search\n- Wiki-links: [[Rust Ownership Model]]\n- Knowledge graph\n- Version history\n- Export to Markdown or PDF", null, ['tools']],
            ['Vim Keybindings', "# Essential Vim Commands\n\n## Normal mode\n- `h/j/k/l` \u2014 move\n- `w/b` \u2014 word forward/backward\n- `dd` \u2014 delete line\n- `yy` \u2014 yank line", null, ['linux', 'tools']],
            ['SQL Performance Tips', "# SQL Optimisation\n\n## Indexes\nCreate indexes on frequently queried columns.\n\n```sql\nCREATE INDEX CONCURRENTLY idx_note_created_at\nON note(created_at)\nWHERE deleted_at IS NULL;\n```\n\n## EXPLAIN ANALYZE\nAlways check query plans.", null, ['algorithms', 'web']],
            ['REST API Design', "# REST API Best Practices\n\n1. Use nouns for resources\n2. HTTP verbs for actions\n3. Versioning: `/api/v1/`\n4. Pagination with `page` + `per_page`\n5. Consistent error format", null, ['web', 'research']],
            ['Git Workflow', "# Git Flow\n\n```bash\ngit checkout -b feature/my-feature\ngit add -p\ngit commit -m 'feat: add cool feature'\ngit push origin feature/my-feature\n```\n\n## Commit conventions\n- `feat:` new feature\n- `fix:` bug fix", null, ['tools', 'productivity']],
            ['Math Linear Algebra', "# Linear Algebra Essentials\n\n## Matrices\nMatrix multiplication: A * B = C\n\n## Eigenvalues\nAv = lambda * v\n\n## Applications\n- Machine learning (PCA)\n- Computer graphics\n- Quantum mechanics\n\nSee [[Quantum Mechanics Introduction]].", null, ['math', 'research']],
            ['Database Design Patterns', "# Database Patterns\n\n## Normalization\n- 1NF: atomic values\n- 2NF: no partial dependencies\n- 3NF: no transitive dependencies\n\n## When to denormalize\n- Read-heavy workloads\n\nRelated: [[SQL Performance Tips]]", null, ['algorithms', 'research']],
            ['Security Checklist', "# Application Security\n\n## OWASP Top 10\n- [ ] SQL Injection prevention\n- [ ] XSS prevention\n- [ ] Authentication hardening\n- [ ] Sensitive data encryption\n- [ ] Security headers", null, ['web', 'research']],
            ['Functional Programming', "# Functional Programming\n\n## Core concepts\n- Pure functions: no side effects\n- Immutability: data doesn't change\n- Higher-order functions\n\n```rust\nlet doubled: Vec<i32> = vec![1,2,3]\n    .iter()\n    .map(|x| x * 2)\n    .collect();\n```\n\nSee [[Rust Ownership Model]].", null, ['rust', 'algorithms', 'research']],
            ['CSS Modern Layout', "# Modern CSS\n\n## Grid\n```css\n.container {\n    display: grid;\n    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));\n    gap: 16px;\n}\n```\n\n## Flexbox vs Grid\n- Flexbox: 1D layouts\n- Grid: 2D layouts", null, ['web']],
        ];

        /** @var Note[] $notes */
        $notes = [];
        $notesByTitle = [];

        foreach ($notesData as [$title, $content, $folder, $noteTags]) {
            $note = new Note($title, $content);
            if (null !== $folder) {
                $note->setFolder($folder);
            }
            foreach ($noteTags as $tagName) {
                $note->addTag($tags[$tagName]);
            }
            $manager->persist($note);
            $notes[] = $note;
            $notesByTitle[$title] = $note;
        }
        $manager->flush();

        // ── NoteLinks ─────────────────────────────────────────────────────────
        $linkPairs = [
            ['Rust Ownership Model', 'Memory Safety in Rust'],
            ['Memory Safety in Rust', 'Rust Ownership Model'],
            ['PHP 8.4 New Features', 'Symfony Best Practices'],
            ['Symfony Best Practices', 'PHP 8.4 New Features'],
            ['Python Asyncio Guide', 'Python Type Hints'],
            ['Algorithm Complexity', 'Graph Algorithms'],
            ['Graph Algorithms', 'Algorithm Complexity'],
            ['AI Tools Overview', 'Machine Learning Basics'],
            ['Machine Learning Basics', 'AI Tools Overview'],
            ['Quantum Mechanics Introduction', 'Quantum Computing Basics'],
            ['Quantum Computing Basics', 'Quantum Mechanics Introduction'],
            ['Math Linear Algebra', 'Quantum Mechanics Introduction'],
            ['Database Design Patterns', 'SQL Performance Tips'],
            ['Functional Programming', 'Rust Ownership Model'],
            ['Getting Started', 'Rust Ownership Model'],
        ];

        foreach ($linkPairs as [$sourceTitle, $targetTitle]) {
            if (isset($notesByTitle[$sourceTitle], $notesByTitle[$targetTitle])) {
                $link = new NoteLink($notesByTitle[$sourceTitle], $notesByTitle[$targetTitle]);
                $manager->persist($link);
            }
        }
        $manager->flush();

        // ── NoteVersions (5 notes with 2 prior versions each) ─────────────────
        $notesWithHistory = [
            'Rust Ownership Model',
            'PHP 8.4 New Features',
            'Algorithm Complexity',
            'AI Tools Overview',
            'Quantum Mechanics Introduction',
        ];

        foreach ($notesWithHistory as $noteTitle) {
            $note = $notesByTitle[$noteTitle];
            for ($v = 1; $v <= 2; ++$v) {
                $version = new NoteVersion(
                    $note,
                    $note->getTitle()." (v{$v})",
                    "Previous version {$v} of: ".($note->getContent() ?? ''),
                    $v,
                );
                $manager->persist($version);
            }
        }
        $manager->flush();
    }
}
