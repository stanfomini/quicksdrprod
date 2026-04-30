<x-mail::message>
@if ($forRespondent)
# Thanks for your response

We've received your submission to **{{ $survey->title }}**. We'll be in touch soon.
@else
# New survey response

A new response was submitted to **{{ $survey->title }}**.

@if ($response->email)
**From:** {{ $response->email }}
@endif

**Submitted:** {{ $response->completed_at?->format('Y-m-d H:i') }}

@if ($response->durationSeconds())
**Time to complete:** {{ intdiv($response->durationSeconds(), 60) }}m {{ $response->durationSeconds() % 60 }}s
@endif

@foreach ($response->answers as $answer)
**{{ $answer->question?->label ?? 'Question #'.$answer->survey_question_id }}**

{{ is_array($answer->value['answer'] ?? null) ? implode(', ', $answer->value['answer']) : ($answer->value['answer'] ?? '—') }}

@endforeach
@endif

@if (! $forRespondent)
<x-mail::button :url="url('/internal/surveys/'.$survey->id.'/stats')">
View all responses
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
