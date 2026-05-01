<?php

namespace App\Filament\Resources\Surveys\RelationManagers;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\SurveyCsvExporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Responses';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),
                IconColumn::make('completed_at')
                    ->label('Completed')
                    ->boolean(),
                TextColumn::make('completed_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(fn (SurveyResponse $record): string => self::formatDuration($record->durationSeconds())),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('completed_at')
                    ->label('Completed')
                    ->nullable(),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => $this->exportCsv()),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (SurveyResponse $record) => 'Response #'.$record->id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (SurveyResponse $record): HtmlString => self::renderResponseView($record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return $minutes > 0 ? $minutes.'m '.$rest.'s' : $seconds.'s';
    }

    protected static function renderResponseView(SurveyResponse $response): HtmlString
    {
        $response->loadMissing(['answers.question', 'survey.questions']);

        $rows = collect($response->survey->questions)->map(function ($question) use ($response): string {
            $answer = $response->answers->firstWhere('survey_question_id', $question->id);
            $value = $answer?->value['answer'] ?? null;

            $display = match (true) {
                is_array($value) => implode(', ', array_map('strval', $value)),
                $value === null || $value === '' => '<em class="text-gray-400">No answer</em>',
                default => e((string) $value),
            };

            return '<div class="py-2 border-b border-gray-100 dark:border-gray-800">'.
                '<div class="text-xs text-gray-500">'.e($question->label).'</div>'.
                '<div class="mt-1">'.$display.'</div>'.
                '</div>';
        })->implode('');

        $meta = '<div class="mt-4 text-xs text-gray-500">'.
            'Started: '.($response->started_at?->format('Y-m-d H:i') ?? '—').' · '.
            'Completed: '.($response->completed_at?->format('Y-m-d H:i') ?? '—').' · '.
            'Email: '.e($response->email ?? '—').
            '</div>';

        return new HtmlString('<div class="space-y-1">'.$rows.$meta.'</div>');
    }

    protected function exportCsv(): StreamedResponse
    {
        /** @var Survey $survey */
        $survey = $this->getOwnerRecord();

        return app(SurveyCsvExporter::class)->streamDownload($survey);
    }
}
