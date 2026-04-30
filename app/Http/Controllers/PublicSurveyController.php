<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;

class PublicSurveyController extends Controller
{
    public function __invoke(string $token): View
    {
        $response = SurveyResponse::query()
            ->where('token', $token)
            ->with('survey.questions')
            ->firstOrFail();

        abort_if($response->survey === null, 404);

        return view('pages.public-survey', [
            'survey' => $response->survey,
            'response' => $response,
        ]);
    }
}
