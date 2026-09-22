<?php
namespace App\Enums;
enum ReplacementStatus:string { case Requested='requested';case UnderReview='under_review';case Approved='approved';case WorkerSearch='worker_search';case Shortlisted='shortlisted';case ReplacementAssigned='replacement_assigned';case Completed='completed';case Rejected='rejected';case Cancelled='cancelled'; public function label():string{return str($this->value)->headline()->toString();} }
