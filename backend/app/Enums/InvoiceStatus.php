<?php
namespace App\Enums;
enum InvoiceStatus:string {case Draft='draft';case Generated='generated';case Sent='sent';case Cancelled='cancelled';public function label():string{return str($this->value)->headline()->toString();}public function isReadOnly():bool{return $this===self::Cancelled;}}
