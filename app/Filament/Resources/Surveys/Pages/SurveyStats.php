<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Filament\Resources\Surveys\SurveyResource;
use App\Services\SurveyStatistics;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SurveyStats extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SurveyResource::class;

    protected string $view = 'filament.resources.surveys.pages.survey-stats';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Stats — '.$this->getRecord()->title;
    }

    public function getBreadcrumb(): string
    {
        return 'Stats';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to survey')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => SurveyResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    public function getStatistics(): SurveyStatistics
    {
        return new SurveyStatistics($this->getRecord());
    }
}
