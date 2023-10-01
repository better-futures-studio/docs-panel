<?php

namespace AbdelElrafa\DocsPanel;

use AbdelElrafa\DocsPanel\Pages\DocsPages;
use Filament\Facades\Filament;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DocsPanelServiceProvider extends PackageServiceProvider
{
    public static string $name = 'docs-panel';

    public static string $viewNamespace = 'docs-panel';

    /**
     * @var array<int, string>
     */
    public static $groupsOrder = [];

    /**
     * @var array<\Closure>
     */
    protected static $modifyPanelUsing = [];

    protected static bool $enableThemeSelector = true;

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        Filament::registerPanel(
            $this->panel(Panel::make()),
        );
    }

    protected function getAssetPackageName(): ?string
    {
        return 'abdelelrafa/docs-panel';
    }

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id(self::$name)
            ->path('docs')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->pages([
                DocsPages::class,
            ])
            ->middleware([
                EncryptCookies::class,
                VerifyCsrfToken::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $docs = DocsPages::getDocs();
                $navigationGroups = [];
                $panel = filament()->getPanel(self::$name)->getId();

                collect($docs)
                    ->sortBy('title')
                    ->sortBy('order')
                    ->groupBy('group')
                    ->sortKeys()
                    ->sortBy(fn ($docs, $group) => ($order = array_search($group, self::$groupsOrder)) !== false ? $order : PHP_INT_MAX)
                    ->each(function ($docs, $group) use (&$navigationGroups, $panel) {
                        $navigationItems = [];
                        foreach ($docs as $file) {
                            $routePath = rtrim(filament()->getPanel(self::$name)->getPath(), '/') . '/' . $file['slug'];

                            if (empty($routeIsActive)) {
                                $routeIsActive = request()->routeIs("filament.{$panel}.pages.{$file['slug']}");
                            }

                            $navigationItems[] = NavigationItem::make($file['title'])
                                ->group($group)
                                ->isActiveWhen(fn (): bool => request()->routeIs("filament.{$panel}.pages.{$file['slug']}"))
                                ->sort(DocsPages::getNavigationSort())
                                ->badge(DocsPages::getNavigationBadge(), color: DocsPages::getNavigationBadgeColor())
                                ->url("{$routePath}");
                        }
                        if (empty($navigationItems)) {
                            return;
                        }
                        $navigationGroups[] = NavigationGroup::make($group)
                            ->collapsed(! $routeIsActive)
                            ->collapsible()
                            ->items($navigationItems);
                    });

                return $builder->groups($navigationGroups);
            });

        $panel->renderHook('panels::topbar.end', fn () => self::$enableThemeSelector && ($panel->hasDarkMode() && ! $panel->hasDarkModeForced()) ? Blade::render('<x-filament-panels::theme-switcher />') : '');

        foreach (self::$modifyPanelUsing as $modifyPanelUsing) {
            $panel = $modifyPanelUsing($panel);
        }

        return $panel;
    }

    /**
     * @param  array<int, string>  $groupsOrder
     */
    public static function setGroupsOrder(array $groupsOrder): void
    {
        self::$groupsOrder = $groupsOrder;
    }

    public static function modifyPanelUsing(\Closure $closure): void
    {
        self::$modifyPanelUsing[] = $closure;
    }

    public static function disableThemeSelector(): void
    {
        self::$enableThemeSelector = false;
    }
}
