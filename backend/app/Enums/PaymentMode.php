<?php
namespace App\Enums;
enum PaymentMode:string { case Cash='cash'; case Upi='upi'; case BankTransfer='bank_transfer'; case Cheque='cheque'; case Other='other'; public function label():string{return str($this->value)->replace('_',' ')->headline()->toString();} }
