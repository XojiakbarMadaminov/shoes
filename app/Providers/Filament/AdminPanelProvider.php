<?php

namespace App\Providers\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#484ab5'), // Color::hex('#b5972d') // Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->topNavigation()
            ->brandName('Tractor')
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->userMenuItems([
                Action::make('switchStore')
                    ->label('Store tanlash')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(fn() => auth()->user()?->stores()->pluck('stores.name', 'stores.id') ?? [])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $user = auth()->user();
                        if ($user && $user->stores->contains($data['store_id'])) {
                            $user->update(['current_store_id' => $data['store_id']]);

                            Notification::make()
                                ->title('Store yangilandi')
                                ->body('Hozirgi store: ' . $user->currentStore?->name)
                                ->success()
                                ->send();
                        }
                    })
            ]);


    }
}
