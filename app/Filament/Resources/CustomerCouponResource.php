<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerCouponResource\Pages;
use App\Filament\Resources\CustomerCouponResource\RelationManagers;
use App\Models\CustomerCoupon;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerCouponResource extends Resource
{
    protected static ?string $model = CustomerCoupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Customers';
    protected static ?string $navigationLabel = 'Customer Coupons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('coupon_handle')
                    ->label('Coupon')
                    ->options(Coupon::pluck('name', 'handle'))
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('customer_phone')
                    ->label('Customer Phone')
                    ->required()
                    ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('coupon_handle')->label('Coupon'),
                Tables\Columns\TextColumn::make('customer_phone')->label('Customer Phone'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Created'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCustomerCoupons::route('/'),
            'create' => Pages\CreateCustomerCoupon::route('/create'),
            'edit' => Pages\EditCustomerCoupon::route('/{record}/edit'),
        ];
    }
}
