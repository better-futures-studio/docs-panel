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
    protected static $groupsOrder = [];

    /**
     * @var array<\Closure>
     */
    protected static $modifyPanelUsing = [];

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        Filament::registerPanel(
            $this->panel(Panel::make()),
        );
    }

    public function packageBooted(): void
    {
        //
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
            // ->discoverResources(in: app_path('Filament/Docs/Resources'), for: 'App\\Filament\\Docs\\Resources')
            // ->discoverPages(in: app_path('Filament/Docs/Pages'), for: 'App\\Filament\\Docs\\Pages')
            ->pages([
                DocsPages::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Docs/Widgets'), for: 'App\\Filament\\Docs\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
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
            ->authMiddleware([
                // Authenticate::class,
            ])
            // ->renderHook('panels::topbar.end', fn () => Blade::render('<x-filament-panels::theme-switcher />'))
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
                            $routePath = rtrim(filament()->getPanel(self::$name)->getPath(), '/') . '/' . $file['route_path'];

                            if (empty($routeIsActive)) {
                                $routeIsActive = request()->routeIs("filament.{$panel}.pages.{$file['slug']}");
                            }

                            $navigationItems[] = NavigationItem::make($file['title'])
                                ->group($group)
                                ->icon(DocsPages::getNavigationIcon())
                                ->activeIcon(DocsPages::getActiveNavigationIcon())
                                ->isActiveWhen(fn (): bool => request()->routeIs("filament.{$panel}.pages.{$file['slug']}"))
                                ->sort(DocsPages::getNavigationSort())
                                ->badge(DocsPages::getNavigationBadge(), color: DocsPages::getNavigationBadgeColor())
                                ->url("/{$routePath}");
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
}
