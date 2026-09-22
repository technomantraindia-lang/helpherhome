<?php
namespace App\Enums;
enum PaymentStatus:string {case Unpaid='unpaid';case PartiallyPaid='partially_paid';case Paid='paid';case Overdue='overdue';public function label():string{return str($this->value)->headline()->toString();}}
