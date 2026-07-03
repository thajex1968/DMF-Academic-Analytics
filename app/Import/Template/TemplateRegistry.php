<?php

declare(strict_types=1);

namespace DMF\Import\Template;

use RuntimeException;

/**
 * Looks up an ImportTemplate by its registry key (e.g. "ONET-2569"). In
 * memory only — a database-backed registry (so an administrator can add a
 * template without a deployment, matching PRD FR-019's Approval Flow spirit)
 * is a later increment; this is the lookup mechanism it would sit behind.
 */
final class TemplateRegistry
{
    /** @var array<string, ImportTemplate> */
    private array $templates = [];

    public function register(ImportTemplate $template): void
    {
        $this->templates[$template->key] = $template;
    }

    public function has(string $key): bool
    {
        return isset($this->templates[$key]);
    }

    /** @throws RuntimeException If no template is registered for $key. */
    public function get(string $key): ImportTemplate
    {
        if (!isset($this->templates[$key])) {
            throw new RuntimeException(sprintf('No import template is registered for key "%s".', $key));
        }

        return $this->templates[$key];
    }

    /** @return string[] Every registered template key. */
    public function keys(): array
    {
        return array_keys($this->templates);
    }
}
