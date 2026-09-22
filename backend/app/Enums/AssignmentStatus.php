<?php
namespace App\Enums;
enum AssignmentStatus:string { case Draft='draft';case Confirmed='confirmed';case Active='active';case Completed='completed';case Cancelled='cancelled';case Replaced='replaced'; public function label():string{return str($this->value)->headline()->toString();} public static function active():array{return [self::Confirmed->value,self::Active->value];} public static function history():array{return [self::Completed->value,self::Cancelled->value,self::Replaced->value];} }
