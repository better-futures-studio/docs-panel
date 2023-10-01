<?php

namespace BetterFuturesStudio\DocsPanel;

use BetterFuturesStudio\DocsPanel\Pages\DocsPages;
use Filament\Facades\Filament;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
        return 'better-futures-studio/docs-panel';
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
            ]);

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
