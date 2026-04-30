<?php

namespace App\Models;

use App\Enums\SurveyAudience;
use App\Enums\SurveyStatus;
use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'brand_color' => '#10b981',
        'show_progress_bar' => true,
        'multi_step' => false,
        'notify_respondent' => false,
        'require_unique_email' => false,
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'audience' => SurveyAudience::class,
            'status' => SurveyStatus::class,
            'settings' => 'array',
            'published_at' => 'datetime',
            'show_progress_bar' => 'boolean',
            'multi_step' => 'boolean',
            'notify_respondent' => 'boolean',
            'require_unique_email' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    /**
     * Only the questions that ask the respondent for an answer
     * (i.e. excluding Section / Description layout blocks).
     */
    public function answerableQuestions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)
            ->whereNotIn('type', ['section', 'description'])
            ->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function isPublished(): bool
    {
        return $this->status === SurveyStatus::Published;
    }

    public function acceptsResponses(): bool
    {
        return $this->status?->acceptsResponses() ?? false;
    }

    public function publicUrl(): string
    {
        return route('public-survey.show', $this->slug);
    }

    /**
     * Detect the kind of video URL set on cover_video_url so the renderer
     * knows which embed strategy to use.
     *
     * @return array{kind: 'youtube'|'vimeo'|'file'|'iframe'|'none', url: ?string, embed: ?string}
     */
    public function coverVideoEmbed(): array
    {
        $url = trim((string) ($this->cover_video_url ?? ''));
        if ($url === '') {
            return ['kind' => 'none', 'url' => null, 'embed' => null];
        }

        // YouTube — accept youtu.be/<id> and youtube.com/watch?v=<id> and shorts
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m)) {
            return [
                'kind' => 'youtube',
                'url' => $url,
                'embed' => 'https://www.youtube.com/embed/'.$m[1].'?rel=0',
            ];
        }

        // Vimeo — vimeo.com/<numeric_id>
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return [
                'kind' => 'vimeo',
                'url' => $url,
                'embed' => 'https://player.vimeo.com/video/'.$m[1],
            ];
        }

        // Direct video file
        if (preg_match('~\.(mp4|webm|mov|ogg|m4v)(\?.*)?$~i', $url)) {
            return ['kind' => 'file', 'url' => $url, 'embed' => $url];
        }

        // Anything else is treated as a generic iframe URL (Loom, Wistia, etc.)
        return ['kind' => 'iframe', 'url' => $url, 'embed' => $url];
    }

    public function embedUrl(): string
    {
        return route('public-survey.embed', $this->slug);
    }

    public function embedSnippet(int $height = 700): string
    {
        return sprintf(
            '<iframe src="%s" width="100%%" height="%d" frameborder="0" style="border:0;"></iframe>',
            e($this->embedUrl()),
            $height,
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', SurveyStatus::Published);
    }

    public function duplicate(): self
    {
        return tap($this->replicate(['published_at']), function (self $copy): void {
            $copy->title = $this->title.' (copy)';
            $copy->slug = $this->slug.'-copy-'.Str::lower(Str::random(4));
            $copy->status = SurveyStatus::Draft;
            $copy->published_at = null;
            $copy->save();

            foreach ($this->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->survey_id = $copy->id;
                $newQuestion->save();
            }
        });
    }
}
