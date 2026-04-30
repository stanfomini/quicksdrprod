<?php

namespace App\Http\Controllers;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicSurveyController extends Controller
{
    public function showBySlug(string $slug, Request $request): View
    {
        [$survey, $isPreview] = $this->findSurvey($slug, $request);

        return view('pages.public-survey', [
            'survey' => $survey,
            'response' => null,
            'embed' => false,
            'isPreview' => $isPreview,
        ]);
    }

    public function embedBySlug(string $slug, Request $request): View
    {
        [$survey, $isPreview] = $this->findSurvey($slug, $request);

        return view('pages.public-survey', [
            'survey' => $survey,
            'response' => null,
            'embed' => true,
            'isPreview' => $isPreview,
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
            'isPreview' => false,
        ]);
    }

    /**
     * @return array{0: Survey, 1: bool}
     */
    protected function findSurvey(string $slug, Request $request): array
    {
        $survey = Survey::query()
            ->where('slug', $slug)
            ->with('questions')
            ->firstOrFail();

        if ($survey->status === SurveyStatus::Published) {
            return [$survey, false];
        }

        // Allow authenticated internal users to preview unpublished surveys.
        $user = $request->user();
        if ($user !== null && method_exists($user, 'isInternal') && $user->isInternal()) {
            return [$survey, true];
        }

        abort(404);
    }
}
