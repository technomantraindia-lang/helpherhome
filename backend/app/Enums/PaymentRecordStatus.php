<?php
namespace App\Enums;
enum PaymentRecordStatus:string { case Pending='pending'; case Completed='completed'; case Failed='failed'; case Cancelled='cancelled'; case Refunded='refunded'; public function label():string{return str($this->value)->replace('_',' ')->headline()->toString();} }
