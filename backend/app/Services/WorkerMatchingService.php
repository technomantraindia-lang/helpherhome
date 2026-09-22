<?php
namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\WorkerStatus;
use App\Models\CustomerRequirement;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WorkerMatchingService
{
    public function candidates(CustomerRequirement $requirement, array $filters=[] , ?int $excludeWorkerId=null): Collection
    {
        $requirement->loadMissing(['customer','service','dutyType','workerPreference']);
        $query=Worker::query()->with(['services:id,name','preferredDutyType:id,name','verification'])
            ->where('worker_status',WorkerStatus::Active->value)
            ->whereHas('services',fn($q)=>$q->where('services.id',$filters['service_id']??$requirement->service_id));
        if(!($filters['show_unavailable']??false))$query->whereNotIn('availability_status',[AvailabilityStatus::Working->value,AvailabilityStatus::Unavailable->value]);
        if($excludeWorkerId)$query->whereKeyNot($excludeWorkerId);
        $query->when($filters['gender']??null,fn(Builder $q,$v)=>$q->where('gender',$v))
            ->when($filters['availability']??null,fn(Builder $q,$v)=>$q->where('availability_status',$v))
            ->when($filters['duty_type_id']??null,fn(Builder $q,$v)=>$q->where('preferred_duty_type_id',$v))
            ->when($filters['city']??null,fn(Builder $q,$v)=>$q->where('city',$v))
            ->when($filters['experience_min']??null,fn(Builder $q,$v)=>$q->where('years_of_experience','>=',$v))
            ->when($filters['salary_max']??null,fn(Builder $q,$v)=>$q->where(fn($x)=>$x->whereNull('salary_expectation')->orWhere('salary_expectation','<=',$v)))
            ->when($filters['verified_only']??false,fn(Builder $q)=>$q->whereHas('verification',fn($v)=>$v->where('overall_verification_status','verified')));
        return $query->get()->map(function(Worker $worker)use($requirement){$match=$this->score($requirement,$worker);$worker->setAttribute('match_score',$match['score']);$worker->setAttribute('match_reasons',$match['reasons']);return $worker;})->sortByDesc('match_score')->values();
    }

    public function score(CustomerRequirement $r,Worker $w):array
    {
        $reasons=[];$score=30;$reasons['Service Match']=30;
        $duty=$r->duty_type_id&&$w->preferred_duty_type_id===$r->duty_type_id?15:0;$score+=$duty;$reasons['Duty Match']=$duty;
        $gender=!$r->required_gender||in_array($r->required_gender,['both','no_preference'])||$w->gender===$r->required_gender?10:0;$score+=$gender;$reasons['Gender']=$gender;
        $location=($r->customer->city&&strcasecmp($r->customer->city,$w->city)===0)||($r->customer->location&&stripos((string)$w->preferred_work_location,$r->customer->location)!==false)?10:0;$score+=$location;$reasons['Location']=$location;
        $years=(float)($w->years_of_experience??0);$needed=match($r->workerPreference?->experience_requirement?->value){'1_2_years'=>1,'2_5_years'=>2,'5_plus_years'=>5,default=>0};$experience=$years >= $needed?10:0;$score+=$experience;$reasons['Experience']=$experience;
        $salary=!$r->monthly_salary_budget||!$w->salary_expectation||(float)$w->salary_expectation<=(float)$r->monthly_salary_budget?10:0;$score+=$salary;$reasons['Salary']=$salary;
        $verified=$w->verification?->overall_verification_status?->value==='verified'?10:0;$score+=$verified;$reasons['Verification']=$verified;
        $availability=$w->availability_status===AvailabilityStatus::Available?5:0;$score+=$availability;$reasons['Availability']=$availability;
        return ['score'=>$score,'reasons'=>$reasons];
    }
}
