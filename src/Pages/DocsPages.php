<?php

namespace BetterFuturesStudio\DocsPanel\Pages;

use BetterFuturesStudio\DocsPanel\DocsPanelServiceProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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
        $files = collect(static::getDocs())
            ->sortBy('title')
            ->sortBy('order')
            ->groupBy('group')
            ->sortKeys()
            ->sortBy(fn ($docs, $group) => ($order = array_search($group, DocsPanelServiceProvider::$groupsOrder)) !== false ? $order : PHP_INT_MAX)
            ->flatten(1);

        // Register the root route to redirect to the first page.
        Route::get('/', fn () => redirect()->route("filament.{$panel->getId()}.pages.{$files->first()['slug']}"))
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name('index');

        foreach ($files as $file) {
            $routePath = $file['slug'];

            Route::get("/{$routePath}", static::class)
                ->middleware(static::getRouteMiddleware($panel))
                ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
                ->name($file['slug']);
        }
    }

    /**
     * @return array<array{path: string, slug: string, group: string, order: int, title: string, content: string}>
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
                    $title = Str::of(basename($file->getRelativePathname()))
                        ->beforeLast('.md')
                        ->headline();
                }

                $docs[] = [
                    'path' => $file->getRelativePathname(),
                    'slug' => $object->matter('slug')
                        ?: Str::of($file->getRelativePathname())->replace(['index.md', '.md'], '')->afterLast(DIRECTORY_SEPARATOR)
                        ?: 'index',
                    'group' => $object->matter('group')
                        ?: (
                            Str::contains($file->getRelativePathname(), DIRECTORY_SEPARATOR)
                            ? Str::of($file->getRelativePathname())->before(DIRECTORY_SEPARATOR)->headline()
                            : ''
                        ),
                    'order' => $object->matter('order')
                        ?: PHP_INT_MAX,
                    'title' => $title
                        ?: 'Get Started',
                    'content' => $object->body(),
                ];
            }

            return $docs;
        });
    }

    public function getTitle(): string|Htmlable
    {
        $panelName = DocsPanelServiceProvider::$name;
        foreach (static::getDocs() as $file) {
            if (request()->routeIs("filament.{$panelName}.pages.{$file['slug']}")) {
                return $file['title'];
            }
        }

        return 'Docs';
    }

    public static function getNavigationItems(): array
    {
        $docs = DocsPages::getDocs();
        $navigationItems = [];
        $panelId = ($panel = filament()->getCurrentPanel())->getId();

        $navigationGroups = [];
        foreach ($docs as $doc) {
            $navigationGroups[strval($doc['group'])] = $navigationGroups[strval($doc['group'])] ?? request()->routeIs("filament.{$panelId}.pages.{$doc['slug']}");
        }
        $navigationGroups = collect($navigationGroups)
            ->sortKeys()
            ->sortBy(fn ($docs, $group) => ($order = array_search($group, DocsPanelServiceProvider::$groupsOrder)) !== false ? $order : PHP_INT_MAX)
            ->toArray();
        foreach ($navigationGroups as $group => $routeIsActive) {
            $groupsToRegister[] = NavigationGroup::make($group)
                ->collapsed(! $routeIsActive)
                ->collapsible();
        }

        $panel->navigationGroups($groupsToRegister ?? []);

        collect($docs)
            ->sortBy('title')
            ->sortBy('order')
            ->each(function ($file) use (&$navigationItems, $panelId) {
                $navigationItems[] = NavigationItem::make($file['title'])
                    ->group($file['group'])
                    ->isActiveWhen(fn (): bool => request()->routeIs("filament.{$panelId}.pages.{$file['slug']}"))
                    ->sort(DocsPages::getNavigationSort())
                    ->badge(DocsPages::getNavigationBadge(), color: DocsPages::getNavigationBadgeColor())
                    ->url("{$file['slug']}");
            });

        return $navigationItems;
    }
}
