<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditSurvey extends EditRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (Survey $record): string => $record->publicUrl())
                ->openUrlInNewTab(),

            Action::make('shareLink')
                ->label('Share link')
                ->icon('heroicon-o-link')
                ->modalHeading('Public link')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (Survey $record): HtmlString => new HtmlString(
                    '<div class="space-y-3">'.
                        '<p class="text-sm text-gray-600">Share this link with respondents.</p>'.
                        '<input type="text" readonly value="'.e($record->publicUrl()).'" '.
                        'class="w-full rounded border-gray-300 font-mono text-sm" '.
                        'onclick="this.select(); document.execCommand(\'copy\');" />'.
                        '<p class="text-xs text-gray-500">Click the field to copy.</p>'.
                    '</div>'
                )),

            Action::make('embed')
                ->label('Embed code')
                ->icon('heroicon-o-code-bracket')
                ->modalHeading('Embed on your website')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (Survey $record): HtmlString => new HtmlString(
                    '<div class="space-y-3">'.
                        '<p class="text-sm text-gray-600">Paste this snippet into any HTML page.</p>'.
                        '<textarea readonly rows="3" '.
                        'class="w-full rounded border-gray-300 font-mono text-xs" '.
                        'onclick="this.select(); document.execCommand(\'copy\');" '.
                        '>'.e($record->embedSnippet()).'</textarea>'.
                        '<p class="text-xs text-gray-500">Click the box to copy.</p>'.
                    '</div>'
                )),

            Action::make('stats')
                ->label('Stats')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->url(fn (Survey $record): string => SurveyResource::getUrl('stats', ['record' => $record])),

            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->requiresConfirmation()
                ->action(function (Survey $record): void {
                    $copy = $record->duplicate();

                    Notification::make()
                        ->title('Survey duplicated')
                        ->success()
                        ->send();

                    $this->redirect(SurveyResource::getUrl('edit', ['record' => $copy]));
                }),

            DeleteAction::make(),
        ];
    }
}
