<?php

namespace App\Console\Commands;

use App\Models\Audit;
use App\Models\Business;
use App\Models\Division;
use App\Models\Insured;
use App\Models\InsuredBusiness;
use App\Models\Role;
use App\Models\Tag;
use App\Models\Taggable;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ClearDataTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user      = User::where('email', 'chang@hsing.li')->first();
        $userRoles = UserRole::where('user_id', $user->id)->get();
        $roles     = Role::find($userRoles->pluck('role_id')->toArray());
        // $businesses = Business::where('id', 32)->get();
        $businesses = Business::find($roles->pluck('business_id')->toArray());
        // $business   = $businesses;
        $business    = $businesses->where('commercial_name', 'Empaque35');
        $businessIds = $business->pluck('id');
        $roles       = $roles->filter(function ($role) use ($businessIds) {
            return $businessIds->contains($role->business_id);
        });
        $userRoles = $userRoles->filter(function ($userRole) use ($roles) {
            return $roles->contains($userRole->role_id);
        });
        $tags      = Tag::whereIn("business_id", $businessIds)->get();
        $taggables = Taggable::whereIn("tag_id", $tags->pluck('id'))->get();
        $audits    = Audit::where('user_type', UserRole::class)
            ->whereIn('user_id', $userRoles->pluck('id')->toArray())->get();
        $divisions = Division::whereIn('business_id', $businessIds)->get();
        $insureds  = Insured::whereHas('business', function (Builder $query) use ($businessIds) {
            $query->whereIn('business_id', $businessIds);
        })->get();
        $insuredBusiness = InsuredBusiness::whereIn('business_id', $businessIds)->get();
        //Destroy Audits
        Audit::destroy($audits->pluck('id')->toArray());

        //Deleted at to InsuredBusiness
        InsuredBusiness::destroy($insuredBusiness->pluck('id')->toArray());

        //Delete  InsuredBusiness and query
        $insuredBusinessTrashed = InsuredBusiness::withTrashed()
            ->whereIn('business_id', $businessIds)->get();
        $insuredBusinessTrashed->map(function ($insuredBusiness) {
            $insuredBusiness->forceDelete();
        });

        //Deleted at to divisions
        Division::destroy($divisions->pluck('id')->toArray());
        //Delete  divisions and query
        $divisionsTrashed = Division::withTrashed()->whereIn('business_id', $businessIds)->get();
        $divisionsTrashed->map(function ($division) {
            $division->forceDelete();
        });
        //Delete the rest
        Taggable::whereIn('tag_id', $taggables->pluck('tag_id')->toArray())->delete();
        Tag::destroy($tags->pluck('id')->toArray());
        UserRole::destroy($userRoles->pluck('id')->toArray());
        Role::destroy($roles->pluck('id')->toArray());
        Insured::destroy($insureds->pluck('id')->toArray());
        // dd($businessIds);
        Business::destroy($businessIds);
        // $user->delete();
        echo "done";
    }
}
