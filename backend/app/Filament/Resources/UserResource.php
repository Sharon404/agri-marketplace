<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('role')
                    ->required()
                    ->maxLength(255)
                    ->default('buyer'),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('sellerProfile'))
            ->selectable(false)
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sellerProfile.business_name')
                    ->label('Business')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sellerProfile.verification_status')
                    ->label('Seller Verification')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'buyer' => 'Buyer',
                        'seller' => 'Seller',
                        'admin' => 'Admin',
                    ]),
                Tables\Filters\SelectFilter::make('seller_verification')
                    ->label('Seller Verification')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $state = $data['value'] ?? null;

                        if (! $state) {
                            return $query;
                        }

                        return $query->whereHas('sellerProfile', fn (Builder $sellerQuery) => $sellerQuery
                            ->where('verification_status', $state));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verifySeller')
                    ->label('Verify Seller')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->role === 'seller'
                        && $record->sellerProfile !== null
                        && $record->sellerProfile->verification_status !== 'verified')
                    ->action(function (User $record): void {
                        $record->sellerProfile?->update([
                            'verification_status' => 'verified',
                            'verified_at' => now(),
                            'rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Seller verified successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('rejectSeller')
                    ->label('Reject Seller')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->visible(fn (User $record): bool => $record->role === 'seller'
                        && $record->sellerProfile !== null
                        && $record->sellerProfile->verification_status !== 'rejected')
                    ->action(function (User $record, array $data): void {
                        $record->sellerProfile?->update([
                            'verification_status' => 'rejected',
                            'verified_at' => null,
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Seller rejected')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
