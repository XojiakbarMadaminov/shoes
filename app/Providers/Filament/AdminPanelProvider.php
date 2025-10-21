<?php

namespace App\Providers\Filament;

use Filament\Panel;
use App\Filament\Pages\Pos;
use Filament\PanelProvider;
use Filament\Actions\Action;
use App\Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use App\Filament\Pages\MoveProduct;
use App\Filament\Pages\PurchaseEntry;
use Filament\Forms\Components\Select;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\SalesHistoryPage;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Notification;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use App\Filament\Pages\PurchaseHistoryPage;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Debtors\DebtorResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Products\ProductResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Filament\Resources\Suppliers\SupplierResource;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\SupplierDebts\SupplierDebtResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
//            ->profile()
            ->globalSearch(false)
            ->colors([
                'primary' => Color::hex('#484ab5'), // Color::hex('#b5972d') // Color::Amber,
            ])
            ->darkMode()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->maxContentWidth('screen-2xl')
            ->topNavigation()
            ->brandName('Agropos')
            ->navigation(function (NavigationBuilder $nav): NavigationBuilder {
                return $nav->groups([
                    // 1) Asosiy amallar
                    NavigationGroup::make()
                        ->label('Asosiy amallar')
                        ->items([
                            NavigationItem::make('Sotuv')
                                ->icon('heroicon-o-shopping-cart')
                                ->url(Pos::getUrl()),
                            NavigationItem::make('Ta’minotchidan xarid')
                                ->icon('heroicon-o-truck')
                                ->url(PurchaseEntry::getUrl()),
                            NavigationItem::make('Tovarlarni ko‘chirish')
                                ->icon('heroicon-o-arrows-right-left')
                                ->url(MoveProduct::getUrl()),
                        ]),

                    // 2) Tovarlar va katalog
                    NavigationGroup::make()
                        ->label('Tovarlar va katalog')
                        ->items([
                            NavigationItem::make('Tovarlar')
                                ->icon('heroicon-o-rectangle-stack')
                                ->url(ProductResource::getUrl()),
                            NavigationItem::make('Kategoriyalar')
                                ->icon('heroicon-o-tag')
                                ->url(CategoryResource::getUrl()),
                            NavigationItem::make('Sotuv tarixi')
                                ->icon('heroicon-o-clock')
                                ->url(SalesHistoryPage::getUrl()),
                            NavigationItem::make('Xaridlar tarixi')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->url(PurchaseHistoryPage::getUrl()),
                        ]),

                    // 3) Moliya
                    NavigationGroup::make()
                        ->label('Moliya')
                        ->items([
                            NavigationItem::make('Qarzdorlar')
                                ->icon('heroicon-o-users')
                                ->url(DebtorResource::getUrl()),
                            NavigationItem::make('Ta’minotchilar qarzlari')
                                ->icon('heroicon-o-receipt-percent')
                                ->url(SupplierDebtResource::getUrl()),
                            NavigationItem::make('Expense')
                                ->icon('heroicon-o-arrow-trending-down')
                                ->url(ExpenseResource::getUrl()),
                        ]),

                    // 4) Mijozlar va ta’minotchilar
                    NavigationGroup::make()
                        ->label('Mijozlar va ta’minotchilar')
                        ->items([
                            NavigationItem::make('Client')
                                ->icon('heroicon-o-user')
                                ->url(ClientResource::getUrl()),
                            NavigationItem::make('Supplier')
                                ->icon('heroicon-o-truck')
                                ->url(SupplierResource::getUrl()),
                        ]),

                    // 5) Hisobot va tahlil
                    NavigationGroup::make()
                        ->label('Hisobot va tahlil')
                        ->items([
                            NavigationItem::make('Statistika')
                                ->icon('heroicon-o-chart-bar')
                                ->url(Dashboard::getUrl()),
                        ]),
                ]);
            })
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->userMenuItems([
                Action::make('currentStore')
                    ->label(fn () => auth()->user()?->currentStore?->name ?? 'magazin tanlanmagan')
                    ->color(Color::hex('#484ab5'))
                    ->disabled(),
                Action::make('switchStore')
                    ->label('Magazin tanlash')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(fn () => auth()->user()?->stores()->pluck('stores.name', 'stores.id') ?? [])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $user = auth()->user();
                        if ($user && $user->stores->contains($data['store_id'])) {
                            $user->update(['current_store_id' => $data['store_id']]);
                            cache()->forget('active_stocks_for_store_' . auth()->id());
                            Notification::make()
                                ->title('Store yangilandi')
                                ->body('Hozirgi store: ' . $user->currentStore?->name)
                                ->success()
                                ->send();

                            return redirect(request()->header('Referer'));
                        }
                    }),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}
