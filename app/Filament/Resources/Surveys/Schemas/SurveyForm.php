<?php

namespace App\Filament\Resources\Surveys\Schemas;

use App\Enums\SurveyAudience;
use App\Enums\SurveyQuestionType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, ?string $get) {
                                if ($state && empty($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in URLs and references.'),
                        Select::make('audience')
                            ->options(collect(SurveyAudience::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                            ->required(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        DateTimePicker::make('published_at')
                            ->seconds(false)
                            ->helperText('Leave blank to keep the survey unpublished (admin-only).'),
                    ])
                    ->columns(2),

                Section::make('Questions')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('order')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->schema([
                                Select::make('type')
                                    ->options(collect(SurveyQuestionType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                                    ->required()
                                    ->live()
                                    ->default(SurveyQuestionType::Text->value),
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('help_text')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Toggle::make('required')
                                    ->default(false),
                                Repeater::make('options.choices')
                                    ->label('Choices')
                                    ->visible(fn (callable $get): bool => SurveyQuestionType::tryFrom((string) $get('type'))?->needsOptions() ?? false)
                                    ->schema([
                                        TextInput::make('value')->required(),
                                        TextInput::make('label')->required(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->addActionLabel('Add choice')
                                    ->defaultItems(2),
                                TextInput::make('options.max')
                                    ->label('Maximum value')
                                    ->numeric()
                                    ->default(5)
                                    ->visible(fn (callable $get): bool => in_array(
                                        SurveyQuestionType::tryFrom((string) $get('type')),
                                        [SurveyQuestionType::Scale, SurveyQuestionType::Rating],
                                        true,
                                    )),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add question')
                            ->defaultItems(0),
                    ]),
            ])
            ->columns(1);
    }
}
