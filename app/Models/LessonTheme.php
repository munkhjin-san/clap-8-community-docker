<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class LessonTheme extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['survey_completed', 'survey_date'];
    // Latest attempt only (learner theme-card fallback).
    public function lesson_portfolio(){
        return $this->hasOne(LessonPortfolio::class, 'lesson_theme_id')
            ->select('id', 'lesson_theme_id', 'status', 'understand', 'attempt_no', 'salary_issue_id')
            ->orderByDesc('attempt_no')->orderByDesc('id');
    }
    // All portfolio attempts for this theme (one per user per learn).
    public function lesson_portfolios(){
        return $this->hasMany(LessonPortfolio::class, 'lesson_theme_id');
    }
    public function materials() {
        return $this->hasMany(LessonMaterial::class, 'lesson_theme_id');
    }
    public function materialVersions() {
        return $this->hasMany(LessonMaterialVersion::class, 'lesson_theme_id')->orderBy('version_no');
    }
    public function defaultMaterialVersion() {
        return $this->hasOne(LessonMaterialVersion::class, 'lesson_theme_id')->where('is_default', true);
    }
    // Content shown to learners = the theme's default version's materials.
    public function activeMaterials() {
        return $this->hasMany(LessonMaterial::class, 'lesson_theme_id')
            ->whereHas('version', fn ($q) => $q->where('is_default', true));
    }
    public function exam(){
        return $this->hasOne(LessonExam::class, 'lesson_theme_id');
    }
    public function form(){
        return $this->hasOne(CustomForm::class, 'id', 'custom_form_id');
    }
    // 誓約書: one signed copy per learner.
    public function pledge_signatures(){
        return $this->hasMany(LessonPledgeSignature::class, 'lesson_theme_id');
    }
    public function isSurveyCompletedBy($userId)
    {
        return $this->form?->survey_answers?->where('user_id', $userId)->isNotEmpty();
    }
    public function getSurveyCompletedAttribute()
    {
        return $this->form?->survey_answers?->where('user_id', Auth::id())->isNotEmpty();
    }
    public function getSurveyDateAttribute()
    {
        return $this->form?->survey_answers?->where('user_id', Auth::id())->first()?->updated_at;
    }
    public function accessMembers()
    {
        return $this->belongsToMany(User::class, 'lesson_access', 'lesson_theme_id', 'user_id');
    }
    public function categories()
    {
        return $this->belongsToMany(LessonThemeCategory::class, 'lesson_theme_category_theme')
            ->orderBy('lesson_theme_categories.position');
    }
    public function previousVersion()
    {
        return $this->belongsTo(LessonTheme::class, 'previous_version');
    }
    public function nextVersions()
    {
        return $this->hasMany(LessonTheme::class, 'previous_version');
    }
    public function aiConfigs()
    {
        return $this->hasMany(LessonThemeAiConfig::class, 'lesson_theme_id');
    }

    public function personalMaterials()
    {
        return $this->hasMany(LessonPersonalMaterial::class, 'lesson_theme_id');
    }

    // Themes eligible to be chosen as a salary-issue (昇給課題) target.
    // Replaces the legacy hardcoded `id <= 10` filter.
    public function scopeSalaryIssueTarget($query)
    {
        return $query->where('salary_issue_target', 1);
    }

    public function salaryIssues()
    {
        return $this->hasMany(SalaryIssue::class, 'lesson_theme_id');
    }
}
