<?php

namespace Modules\Gamification\Listeners;

use Modules\Assessments\Events\GradingCompleted;
use Modules\Common\Models\SystemSetting;
use Modules\Gamification\Services\GamificationService;

class NotifyGradingCompleted
{
  public function __construct(private GamificationService $gamification) {}

  public function handle(GradingCompleted $event): void
  {
    $attempt = $event->attempt->fresh();

    if (!$attempt) {
      return;
    }

    // Calculate bonus XP based on score percentage
    $exercise = $attempt->exercise;
    $maxScore = $exercise->max_score > 0 ? $exercise->max_score : 100;
    $scorePercentage = ($attempt->score / $maxScore) * 100;

    // Award bonus XP for high scores (above 80%)
    if ($scorePercentage >= 80) {
      $bonusXp = (int) SystemSetting::get("gamification.points.high_score_bonus", 20);

      $this->gamification->awardXp($attempt->user_id, $bonusXp, "bonus", "attempt", $attempt->id, [
        "description" => sprintf(
          "High score bonus (%.1f%%) on exercise #%d",
          $scorePercentage,
          $exercise->id,
        ),
        "allow_multiple" => false,
      ]);
    }
  }
}
