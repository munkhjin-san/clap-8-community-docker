<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DriveNode;
use App\Models\DriveNodeAcl;
use Illuminate\Auth\Access\Response;

class DriveNodePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    // app/Policies/DriveNodePolicy.php
    public function view(User $u, DriveNode $n): Response
    {
        
        // if ($n->visibility === 'public') {
        //     // // public within company: user and node share the same company
        //     // $nodeCompanyId = optional($n->project)->company_id ?? $n->company_id ?? null;
        //     // return $nodeCompanyId && $u->company_id === $nodeCompanyId;
        //     return true;
        // }
        $active_user = $this->active_user($u);
        if ($n->owner_id == $u->id || 
            $u->isProjectManager($n->project_id) || 
            $active_user->id == 610 || 
            $active_user->id == 608 || 
            $u->position_id < 6) return Response::allow();
        // private: explicit ACL on node (or inherited)

        $hasAcl = DriveNodeAcl::where('node_id', $n->id)
                ->where('user_id', $u->id)
                ->exists();
        if ($hasAcl) return Response::allow();
        return Response::denyAsNotFound(__('drive.not_found'));
    }
    public function update(User $u, DriveNode $n): bool
    {
        // Only owner or project manager can modify/move
        if ($n->owner_id == $u->id) return true;
        return $u->isProjectManager($n->project_id);
    }
    public function share(User $u, DriveNode $n): bool
    {
        

        // if you have “manager” via project_members.authority == 1
        return $n->owner_id == $u->id || $u->isProjectManager($n->project_id);
    }
    private function active_user($u){
        return $u;
    }
}
