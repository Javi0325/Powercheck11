<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GimnasioResource\Pages;
use App\Filament\Resources\GimnasioResource\RelationManagers;
use App\Models\Gimnasio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GimnasioResource extends Resource
{
    protected static ?string $model = Gimnasio::class;

    // app/Filament/Resources/GimnasioResource.php
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ubicacion')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->image() // habilita preview y limita a imágenes
                    ->directory('gym_logos') // carpeta dentro del disco
                    ->disk('public') // usa el disco "public"
                    ->visibility('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*'])
                    ->maxSize(2048) // 2 MB
                    ->helperText('Sube un logo (PNG/JPG, máx. 2 MB)')
                    ->openable()     // botón para abrir
                    ->downloadable(), // botón para descargar
                Forms\Components\TextInput::make('celular')
                    ->maxLength(15),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ubicacion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('logo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('celular')
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
            'index' => Pages\ListGimnasios::route('/'),
            'create' => Pages\CreateGimnasio::route('/create'),
            'edit' => Pages\EditGimnasio::route('/{record}/edit'),
        ];
    }
}
