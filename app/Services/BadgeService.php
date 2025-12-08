<?php

namespace App\Services;

use App\Models\User;
use App\Models\ProjectMemberReportNotification;
use App\Models\UserLastRecord;
use App\Models\PostRecord;
use App\Models\NoticeRecord;
use App\Models\taskRecord;
use App\Models\ProjectRecord;
use App\Models\ProjectGoal;
use App\Models\EvaluationRecord;
use App\Models\SalaryIssue;
use App\Models\AssetRecord;
use App\Models\customFieldDataRecord;
use App\Models\CustomfieldRead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

final class BadgeService
{
    public function goalIssueComment(User $user) {
        $goal_badge_count = ProjectMemberReportNotification::where('target_user_id', $user->id)
            ->get();
        return $goal_badge_count;
    }
    public function post(User $user) {
        if(!empty($user)){
            $list = UserLastRecord::where('user_id', '=', $user->id)->where('deleted_flag', '=', 0)->first();
            $recordTypes = [
                'post' => PostRecord::class,
            ];
            
            $post = PostRecord::latest('created_at')->first();
             
            if(empty($list)){
                $newls = new UserLastRecord;
                $newls->user_id = $auth_user_id;
                $newls->last_post = $post->id;
                $newls->save();
                $list = $newls;
            }
            
            $post_from = $list->last_post;            
            $post_to = $post?->id;
            $post_difference = PostRecord::whereBetween('id', [$post_from, $post_to])->count(); 
            if($post_difference > 0){
                $post_difference -= 1 ;
            }
            $result =  $post_difference;

            
            

            return $result;
        }
    }
    public function notice(User $user) {
        $notice = NoticeRecord::where('deleted_flag', 0)->where('created_at', '>', '2023-10-01')->where('user_id', '!=', $user->id)
        ->whereDoesntHave('readers', function ($query) use($user) {
            $query->where('users.id',$user->id);
        })->count();
        return $notice;
    }
    public function membersGoals(User $user) {
        $date = Carbon::now();
        $managinProjectData = $this->members_of_project_managed_by_user($user);
        $selfProjects = $this->projects_participate_by_user($user);
        $projectData = array_merge($managinProjectData, $selfProjects);
        
        if(!count($projectData)){
            return [];
        }
        $goals = $this->goals_fetch_by_users($projectData, $date, $user);
        
        return $goals;
    }
    public function managersGoals(User $user) {
        $projects = ProjectRecord::whereHas('manager')
            ->with('manager:id')
            ->get();
        $projectsData = $projects->map(function($project) {
            return [
                "project_id" => $project->id,
                "members" => $project->manager->pluck('id')->toArray(),
                "type" => "manager"
            ];
        })->toArray();
        
        if(!count($projectsData)){
            return [];
        }
        $goals = $this->goals_fetch_by_users($projectsData, Carbon::now(), $user);

        return $goals;
    }
    private function projects_participate_by_user($user){
        $projects = ProjectRecord::whereHas('members', fn($q) => $q->where('users.id', $user->id))
        ->get();
        $s = $projects->map(fn($project) => [
            "project_id" => $project->id,
            "members" => [$user->id],
            "type" => "member"
        ])->toArray();
        return $s;
    }
    private function goals_fetch_by_users(array $projectData, Carbon $date, User $user){
        $goals = ProjectGoal::where(function ($query) use ($projectData, $date) {
            foreach($projectData as $project){
                $query->orWhere(function ($subQuery) use ($project, $date) {
                    $subQuery->where('project_id', $project['project_id'])->whereIn('user_id', $project['members'])
                    ->when($project['type'] == 'manager', function ($q) use($date) {
                        $q->whereNotIn('user_id', [Auth::id()])->whereIn('status', [2, 7]);
                    })
                    ->when($project['type'] == 'member', function ($q) {
                        $q->whereIn('status', [1, 8]);
                    });
                });
            }
        })
        ->when($user->id == 631, function($q) {
            $q->orWhere('status', 4);
        })
        ->select('id', 'project_id', 'user_id', 'year', 'which_half', 'status')->get();
        return $goals;
    }
    public function salaryIssue(User $user) {
        $date = Carbon::now();
        $year = $date->year;
        $current_half = Carbon::now()->between(Carbon::createFromDate($year, 4, 1), Carbon::createFromDate($year, 9, 30)) ? 'first' : 'second';
        $previous_half = $current_half == 'first' ? 'second' : 'first';
        $previous_year = $current_half == 'first' ? $year - 1 : $year;
        $years = [ $year - 1, $year, $year +1];
        $evaluations = EvaluationRecord::where('mentor_id', $user->id)
            ->where(function ($query) use($previous_year, $previous_half, $year, $current_half) {
                $query->where(function ($q) use($previous_year, $previous_half) {
                    $q->where('year', $previous_year)->where('which_half', $previous_half);
                })->orWhere(function ($q) use($year, $current_half) {
                    $q->where('year', $year)->where('which_half', $current_half);
                });
            })->get();
        $mentee_id = $evaluations->pluck('user_id')->toArray();
        $salary_issues = SalaryIssue::whereHas('project_goal', function ($q) use($previous_year, $previous_half, $year, $current_half) {
            $q->where(function ($query) use($previous_year, $previous_half, $year, $current_half) {
                $query->where(function ($q) use($previous_year, $previous_half) {
                    $q->where('year', $previous_year)->where('which_half', $previous_half);
                })->orWhere(function ($q) use($year, $current_half) {
                    $q->where('year', $year)->where('which_half', $current_half);
                });
            });
        })->whereHas('project_goal')
        ->where(function ($query) use ($mentee_id) {
            $query->where(function ($query) use ($mentee_id){
                $query->whereIn('status', [2, 7])->whereIn('user_id', $mentee_id);
            })->orWhere(function($query){
                $query->whereIn('status', [1, 8])->where('user_id', Auth::id());
            });
        })            
        ->with('project_goal')
        ->get();
        
        $data = [];

        foreach($salary_issues as $issue) {
            $issue_year = $issue->project_goal->year;
            $issue_half = $issue->project_goal->which_half;

            $is_my_mentee = $evaluations->contains(function ($evaluation) use ($issue_year, $issue_half, $user, $issue) {
                return $evaluation->mentor_id == $user->id 
                && $evaluation->year == $issue_year 
                && $evaluation->which_half == $issue_half
                && $evaluation->user_id == $issue->user_id;
            });

            if($is_my_mentee || ($issue->user_id == Auth::id() && ($issue->status == 1 || $issue->status == 8))) {
                $data[] = [
                    'issue_id' => $issue->id,
                    'goal_id' => $issue->project_goal->id,
                    'project_id' => $issue->project_goal->project_id,
                    'user_id' => $issue->user_id,
                    'year' => $issue_year,
                    'which_half' => $issue_half,
                    'status' => $issue->status,
                ];
            }
        }           

        return $data;
    }
    public function asset(User $user) {
        $projects = $this->members_of_project_managed_by_user($user);
        if(empty($projects) && $user->id != 610 && $user->id != 608){
            return [];
        }
        $project_ids = array_map(function($project){
            return $project['project_id'];
        }, $projects);

        $target_assets = AssetRecord::where(function ($query) use ($user, $project_ids) {
            $query->whereHas('requests', function ($query) use ($user, $project_ids) {
                $query->where('status', 1)
                ->where(function($query) use ($project_ids){
                    $query->whereIn('from_project', $project_ids)
                    ->whereHas('steps', function($query){
                        $query->where('value', 1)->whereNull('approved_by');
                    });
                })->orWhere(function($query) use ($project_ids){
                    $query->whereIn('to_project', $project_ids)
                    ->whereHas('steps', function($query){
                        $query->where('value', 3)->whereNull('approved_by');
                    });
                })->orWhere(function($query) use ($user){
                    $query->when($user->id == 610 || $user->id == 608, function($query){
                        $query->whereHas('steps', function($query){
                            $query->whereIn('value', [4,7])->whereNull('approved_by');
                        });
                    });
                });
            });
        })
        ->with('requests')->get();
        return $target_assets;
    }
    private function members_of_project_managed_by_user($user) {
        $projects = ProjectRecord::whereHas('manager', fn($q) => $q->where('users.id', $user->id))
        ->with('members:id')
        ->get();
        $s = $projects->map(fn($project) => [
            "project_id" => $project->id,
            "members" => $project->members->pluck('id')->toArray(),
            "type" => "manager"
        ])->toArray();
        return $s;
    }
    public function taskComment(User $user) {
        $badge_counts = taskRecord::whereHas('taskUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereHas('taskRecord.comments', function ($q) use($user) {
                        $q->whereColumn('task_comments.created_at', '>', 'task_users.checked_at')->whereNot('task_comments.user_id', $user->id);
                    });
            })->with(['comments' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id);
            }])->get();

        $data = $badge_counts->map(function($task) use($user){
            $comments = $task->comments;
            $task_user = $task->taskUsers->where('user_id', $user->id)->first();
            $comments = $comments->filter(function($comment) use($user, $task_user){
                return $comment->user_id != $user->id && $comment->created_at > $task_user->checked_at;
            });
            return [
                'task_id' => $task->id,
                'comments' => count($comments),
                'project_id' => $task->project_record_id
            ];
        });
        return $data;
    }
    public function financeComment(User $user) {
        $userId = $user->id;

        $isDirector = ($user->position_id < 6) || ($userId === 610);

        if ($isDirector) {
            $projectIds = ProjectRecord::query()->pluck('id');
        } else {
            $projectIds = ProjectRecord::query()
                ->whereHas('manager', fn($q) => $q->where('users.id', $userId))
                ->pluck('id');

            if ($projectIds->isEmpty()) {
                [
                    'total_unread' => 0,
                    'projects'     => [],
                ];
            }
        }

        $q = DB::table('project_finance_comments as c')
            ->select(
                'c.project_record_id',
                'c.period',
                DB::raw('COUNT(*) as unread_count')
            )
            // Prefer period-specific read rows
            ->leftJoin('project_finance_last_reads as lrp', function ($j) use ($userId) {
                $j->on('lrp.project_record_id', '=', 'c.project_record_id')
                  ->on('lrp.period', '=', 'c.period')
                  ->where('lrp.user_id', '=', $userId);
            })
            // Fallback to legacy null-period rows to avoid badge spikes
            ->leftJoin('project_finance_last_reads as lrn', function ($j) use ($userId) {
                $j->on('lrn.project_record_id', '=', 'c.project_record_id')
                  ->whereNull('lrn.period')
                  ->where('lrn.user_id', '=', $userId);
            })
            ->whereIn('c.project_record_id', $projectIds)
            ->whereNull('c.deleted_at')       // if SoftDeletes
            ->where('c.user_id', '!=', $userId)
            ->where(function ($w) {
                $w->whereNull(DB::raw('COALESCE(lrp.last_read_at, lrn.last_read_at)'))
                  ->orWhereColumn('c.created_at', '>', DB::raw('COALESCE(lrp.last_read_at, lrn.last_read_at)'));
            })
            ->groupBy('c.project_record_id', 'c.period');
            // no select('*'), or ONLY_FULL_GROUP_BY will yell again

        $rows = $q->get();


        $totalUnread = 0;
        $projects = [];

        foreach ($rows as $r) {
            $projectId = (int) $r->project_record_id;
            $period    = $r->period;
            $count     = (int) $r->unread_count;

            $totalUnread += $count;

            if (!isset($projects[$projectId])) {
                $projects[$projectId] = [
                    'project_id'    => $projectId,
                    'total_unread'  => 0,
                    'period_counts' => [],   // period => count
                ];
            }

            $projects[$projectId]['total_unread'] += $count;
            $projects[$projectId]['period_counts'][$period] = $count;
        }

        $data = [
            'total_unread' => $totalUnread,
            'projects'     => array_values($projects),
        ];

        return $data;
    }
    public function contactComment(User $user) {
        $userId = $user->id;
               
        $rows = DB::table('contact_record_comments as c')
        ->join('contact_record_user as cc', function ($j) use ($userId) {
            $j->on('cc.contact_record_id', '=', 'c.contact_record_id')
              ->where('cc.user_id', '=', $userId);   // only contacts I collaborate on
        })
        ->leftJoin('contact_comment_last_reads as lr', function ($j) use ($userId) {
            $j->on('lr.contact_record_id', '=', 'c.contact_record_id')
              ->where('lr.user_id', '=', $userId);
        })
        ->where('c.user_id', '!=', $userId)
        ->whereRaw('c.created_at > COALESCE(lr.last_read_at, "1970-01-01 00:00:00")')
        ->groupBy('c.contact_record_id')
        ->select('c.contact_record_id', DB::raw('COUNT(*) AS unread_count'))
        ->get();

        $data = $rows->map(fn($r) => [
            'contact_id' => (int) $r->contact_record_id,
            'comments'   => (int) $r->unread_count,
        ])->values();

        return $data;
    }
    public function todayReadable(User $user) {
        $now = Carbon::now()->format('Y-m-d');
        $typeId = 43;
        $latestId = customFieldDataRecord::where('type_id', $typeId)
            ->where('date', $now)
            ->whereNotNull('value_text')
            ->max('id') ?? 0;
        
        $read = CustomfieldRead::where('user_id', $user->id)
            ->where('type_id', $typeId)
            ->first();

        $latestReadId = $read?->last_read_customfield_id ?? 0;

        $hasUnread = $latestId > $latestReadId;

        return [
            'has_unread' => $hasUnread,
            'latest_id'  => $latestId,
        ];
    }
}