<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DriveNode;
use App\Models\DriveNodeAcl;

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
    public function view(User $u, DriveNode $n): bool
    {
        
        // if ($n->visibility === 'public') {
        //     // // public within company: user and node share the same company
        //     // $nodeCompanyId = optional($n->project)->company_id ?? $n->company_id ?? null;
        //     // return $nodeCompanyId && $u->company_id === $nodeCompanyId;
        //     return true;
        // }
        if ($n->owner_id == $u->id || $u->isProjectManager($n->project_id)) return true;
        // private: explicit ACL on node (or inherited)
        return DriveNodeAcl::where('node_id',$n->id)
            ->where('user_id',$u->id)
            ->exists();
    }
    public function update(User $u, DriveNode $n): bool
    {
        if ($n->owner_id == $u->id) return true;

        if ($n->visibility === 'public') {
            return true;
        }

        // private: require explicit editor if you ever add roles; for now owner/manager only
        return $u->isProjectManager($n->project_id);
    }
    public function share(User $u, DriveNode $n): bool
    {
        

        // if you have “manager” via project_members.authority == 1
        return $n->owner_id == $u->id || $u->isProjectManager($n->project_id);
    }

}
