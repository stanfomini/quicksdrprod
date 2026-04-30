<?php

namespace App\Events;

use App\Models\SurveyResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurveyResponseSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public SurveyResponse $response) {}
}
