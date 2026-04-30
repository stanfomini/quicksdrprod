<?php

namespace App\Http\Controllers;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicSurveyController extends Controller
{
    public function showBySlug(string $slug): View
    {
        $survey = $this->findPublishedSurvey($slug);

        return view('pages.public-survey', [
            'survey' => $survey,
            'response' => null,
            'embed' => false,
        ]);
    }

    public function embedBySlug(string $slug): View
    {
        $survey = $this->findPublishedSurvey($slug);

        return view('pages.public-survey', [
            'survey' => $survey,
            'response' => null,
            'embed' => true,
        ]);
    }

    public function showByToken(string $token, Request $request): View
    {
        $response = SurveyResponse::query()
            ->where('token', $token)
            ->with('survey.questions')
            ->firstOrFail();

        abort_if($response->survey === null, 404);

        if (! $response->ip_address) {
            $response->forceFill([
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ])->save();
        }

        return view('pages.public-survey', [
            'survey' => $response->survey,
            'response' => $response,
            'embed' => false,
        ]);
    }

    protected function findPublishedSurvey(string $slug): Survey
    {
        $survey = Survey::query()
            ->where('slug', $slug)
            ->with('questions')
            ->firstOrFail();

        abort_unless(
            $survey->status === SurveyStatus::Published,
            404,
        );

        return $survey;
    }
}
