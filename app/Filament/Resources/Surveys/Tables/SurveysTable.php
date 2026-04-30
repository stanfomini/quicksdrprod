<?php

namespace App\Filament\Resources\Surveys\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SurveysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->toggleable(),
                TextColumn::make('audience')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? $state),
                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions'),
                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Responses'),
                IconColumn::make('published_at')
                    ->boolean()
                    ->label('Published'),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
