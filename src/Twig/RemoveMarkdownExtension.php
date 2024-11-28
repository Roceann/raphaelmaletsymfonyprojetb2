<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RemoveMarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('remove_markdown', [$this, 'removeMarkdown']),
        ];
    }

    public function removeMarkdown(string $content): string
    {
        // Supprime les indicateurs de Markdown
        $content = preg_replace('/\*\*(.*?)\*\*/', '$1', $content); // Gras
        $content = preg_replace('/\*(.*?)\*/', '$1', $content); // Italique
        $content = preg_replace('/\#(.*?)\n/', '$1', $content); // Titres
        // Ajoutez d'autres règles de suppression de Markdown si nécessaire

        return $content;
    }
}