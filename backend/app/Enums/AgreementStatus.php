<?php
namespace App\Enums;
enum AgreementStatus:string {case Draft='draft';case Generated='generated';case Sent='sent';case Signed='signed';case Active='active';case Expired='expired';case Cancelled='cancelled';public function label():string{return str($this->value)->headline()->toString();}public function isReadOnly():bool{return in_array($this,[self::Signed,self::Active,self::Expired,self::Cancelled],true);} }
