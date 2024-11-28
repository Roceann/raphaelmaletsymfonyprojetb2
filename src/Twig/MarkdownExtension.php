<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extra\Markdown\MarkdownExtension as BaseMarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class MarkdownExtension extends AbstractExtension
{
    public function getExtensions(): array
    {
        return [
            new BaseMarkdownExtension(),
        ];
    }

    public function getRuntimeLoaders(): array
    {
        return [
            new FactoryRuntimeLoader([
                MarkdownRuntime::class => function () {
                    return new MarkdownRuntime();
                },
            ]),
        ];
    }
}