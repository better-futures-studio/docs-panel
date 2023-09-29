<?php

namespace AbdelElrafa\DocsPanel\Pages;

use AbdelElrafa\DocsPanel\DocsPanelServiceProvider;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class DocsPages extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'docs-panel::docs-pages';

    protected static ?int $navigationSort = -2;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $panelName = DocsPanelServiceProvider::$name;
        $content = '';
        foreach (static::getDocs() as $file) {
            if (request()->routeIs("filament.{$panelName}.pages.{$file['slug']}")) {
                $content = $file['content'];

                break;
            }
        }

        throw_if(empty($content), 'No content found for this page.');

        return [
            'content' => $content,
        ];
    }

    public static function routes(Panel $panel): void
    {
        $files = static::getDocs();

        foreach ($files as $file) {
            $routePath = $file['route_path'];

            Route::get("/{$routePath}", static::class)
                ->middleware(static::getRouteMiddleware($panel))
                ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
                ->name($file['slug']);
        }
    }

    /**
     * @return array<array{path: string, route_path: string, slug: string, group: string, order: int, title: string, content: string}>
     */
    public static function getDocs(): array
    {
        return once(function () {
            $finder = new Finder();

            try {
                $finder->files()->in(resource_path('docs'))->name('*.md');
            } catch (DirectoryNotFoundException $e) {
                return [];
            }

            $docs = [];
            foreach ($finder as $file) {
                $object = YamlFrontMatter::parse($file->getContents());

                $title = $object->matter('title');
                if (empty($title)) {
                    if (str_contains($file->getRelativePathname(), '/')) {
                        if (str_contains($file->getRelativePathname(), 'index.md')) {
                            $title = Str::of(str_replace(['index.md', '.md'], '', $file->getRelativePathname()))
                                ->replace('/', ' ')
                                ->title();
                        } else {
                            $title = str_replace(['.md'], '', $file->getRelativePathname());
                        }
                    }
                }

                $docs[] = [
                    'path' => $file->getRelativePathname(),
                    'route_path' => str_replace(['index.md', '.md'], '', $file->getRelativePathname()),
                    'slug' => $object->matter('slug') ?: str_replace(['index.md', '.md'], '', $file->getRelativePathname()) ?: 'index',
                    'group' => $object->matter('group') ?: (Str::contains($file->getRelativePathname(), '/') ? Str::of($file->getRelativePathname())->before('/')->headline() : ''),
                    'order' => $object->matter('order') ?: 0,
                    'title' => $title ?: 'Get Started',
                    'content' => $object->body(),
                ];
            }

            return $docs;
        });
    }

    public function getTitle(): string | Htmlable
    {
        $panelName = DocsPanelServiceProvider::$name;
        foreach (static::getDocs() as $file) {
            if (request()->routeIs("filament.{$panelName}.pages.{$file['slug']}")) {
                return $file['title'];
            }
        }

        return 'Docs';
    }
}
