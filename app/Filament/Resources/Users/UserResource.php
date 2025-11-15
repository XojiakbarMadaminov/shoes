<?php

namespace App\Filament\Resources\Users;

use BackedEnum;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Enums\NavigationGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\Schemas\UserInfolist;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|null|\UnitEnum $navigationGroup = NavigationGroup::Settings;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;
    protected static ?string $recordTitleAttribute          = 'Foydalanuvchilar';
    protected static ?int $navigationSort                   = 3;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        if ($record instanceof User && $record->email === 'super@gmail.com') {
            return false;
        }

        return parent::canDelete($record);
    }
}
