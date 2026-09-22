<?php
namespace App\Enums;
enum ShortlistStatus:string { case Shortlisted='shortlisted';case Contacted='contacted';case Interested='interested';case NotInterested='not_interested';case Rejected='rejected';case Selected='selected';case Removed='removed'; public function label():string{return str($this->value)->headline()->toString();} }
