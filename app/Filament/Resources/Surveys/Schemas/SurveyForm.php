<?php

namespace App\Filament\Resources\Surveys\Schemas;

use App\Enums\SurveyAudience;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyStatus;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Details')
                            ->icon('heroicon-o-document-text')
                            ->schema(self::detailsSection()),

                        Tab::make('Questions')
                            ->icon('heroicon-o-queue-list')
                            ->schema([self::questionsRepeater()]),

                        Tab::make('After submit')
                            ->icon('heroicon-o-arrow-right-circle')
                            ->schema(self::afterSubmitSection()),

                        Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
                            ->schema(self::notificationsSection()),

                        Tab::make('Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema(self::brandingSection()),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @return array<int, mixed>
     */
    protected static function detailsSection(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if ($state && empty($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Used in the public URL: /s/{slug}'),
                    Select::make('audience')
                        ->options(collect(SurveyAudience::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->required(),
                    Select::make('status')
                        ->options(collect(SurveyStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->default(SurveyStatus::Draft->value)
                        ->required(),
                    Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('Internal notes — not shown to respondents.'),
                    Textarea::make('intro_message')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Optional intro screen shown to respondents before they answer the first question.'),
                ])
                ->columns(2),
        ];
    }

    protected static function questionsRepeater(): Repeater
    {
        $typeOptions = collect(SurveyQuestionType::cases())
            ->groupBy(fn (SurveyQuestionType $t) => $t->group())
            ->map(fn ($cases) => collect($cases)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
            ->all();

        return Repeater::make('questions')
            ->relationship()
            ->orderColumn('order')
            ->reorderable()
            ->collapsible()
            ->cloneable()
            ->itemLabel(function (array $state): ?string {
                $label = $state['label'] ?? null;
                if (! empty($state['starts_new_step'])) {
                    return '↳ NEW STEP — '.$label;
                }

                return $label;
            })
            ->schema([
                Select::make('type')
                    ->options($typeOptions)
                    ->required()
                    ->live()
                    ->default(SurveyQuestionType::Text->value),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Textarea::make('help_text')
                    ->rows(2)
                    ->columnSpanFull()
                    ->helperText('Sub-text shown below the field label.'),
                TextInput::make('placeholder')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => in_array(
                        SurveyQuestionType::tryFrom((string) $get('type')),
                        [
                            SurveyQuestionType::Text, SurveyQuestionType::Textarea,
                            SurveyQuestionType::Name, SurveyQuestionType::Email,
                            SurveyQuestionType::Phone, SurveyQuestionType::Number,
                            SurveyQuestionType::Url,
                        ],
                        true,
                    )),
                Toggle::make('required')
                    ->default(false)
                    ->visible(fn (Get $get): bool => SurveyQuestionType::tryFrom((string) $get('type'))?->isAnswerable() ?? false),
                Toggle::make('starts_new_step')
                    ->label('Starts a new step / page')
                    ->default(false)
                    ->helperText('When the survey is in multi-step mode, this question begins a fresh page. Only takes effect if "Multi-step" is on in the Branding tab.'),
                Repeater::make('options.choices')
                    ->label('Choices')
                    ->visible(fn (Get $get): bool => SurveyQuestionType::tryFrom((string) $get('type'))?->needsOptions() ?? false)
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
                    ->visible(fn (Get $get): bool => in_array(
                        SurveyQuestionType::tryFrom((string) $get('type')),
                        [SurveyQuestionType::Scale, SurveyQuestionType::Rating],
                        true,
                    )),
            ])
            ->columns(2)
            ->addActionLabel('Add question')
            ->defaultItems(0);
    }

    /**
     * @return array<int, mixed>
     */
    protected static function afterSubmitSection(): array
    {
        return [
            Section::make()
                ->schema([
                    Textarea::make('thank_you_message')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Shown after the respondent submits. Leave blank to use the default ("Thanks!").'),
                    TextInput::make('success_redirect_url')
                        ->url()
                        ->columnSpanFull()
                        ->helperText('If set, redirect the respondent to this URL after they submit. Overrides the thank-you message.'),
                ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function notificationsSection(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('email_notification_to')
                        ->label('Notify this email on every submission')
                        ->email()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Toggle::make('notify_respondent')
                        ->label('Email a confirmation to the respondent')
                        ->helperText('Requires the survey to include an Email question.'),
                    TextInput::make('webhook_url')
                        ->label('Webhook URL')
                        ->url()
                        ->columnSpanFull()
                        ->helperText('Will receive a POST with the response payload.'),
                    Toggle::make('require_unique_email')
                        ->label('Block duplicate submissions from the same email')
                        ->helperText('Requires the survey to include an Email question.'),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function brandingSection(): array
    {
        return [
            Section::make()
                ->schema([
                    ColorPicker::make('brand_color')
                        ->default('#10b981')
                        ->helperText('Primary accent color used on buttons and the progress bar.'),
                    Toggle::make('show_progress_bar')
                        ->default(true),
                    Toggle::make('multi_step')
                        ->label('Multi-step (paged) survey')
                        ->default(false)
                        ->helperText('When on, "Section divider" questions become page breaks — respondents see one section at a time with Next / Back buttons.'),
                    FileUpload::make('cover_image_path')
                        ->image()
                        ->disk('public')
                        ->directory('survey-covers')
                        ->columnSpanFull()
                        ->helperText('Shown above the survey title on the public page.'),
                    TextInput::make('cover_video_url')
                        ->label('Intro video URL')
                        ->url()
                        ->maxLength(500)
                        ->columnSpanFull()
                        ->placeholder('https://www.youtube.com/watch?v=… or https://vimeo.com/… or a direct .mp4 URL')
                        ->helperText('Plays on the intro screen above the title. YouTube, Vimeo, and direct video files (.mp4 / .webm / .mov / .ogg) are supported. If both this and a cover image are set, the video takes the intro slot and the image still shows on form pages.'),
                ])
                ->columns(2),
        ];
    }
}
