@extends('layouts.admin')
@section('title','Verify '.$worker->name)
@section('heading','Verify '.$worker->name)
@section('subheading',$worker->worker_code)
@section('content')
<form class="card grid gap-5 p-6" method="POST" action="{{ route('admin.workers.verification.update',$worker) }}">@csrf @method('PUT')
@foreach([['document','Document Verification'],['police','Police Verification'],['background','Background Verification']] as [$key,$label])<section class="rounded-xl border border-neutral-200 p-4"><h2 class="mb-3 font-black">{{ $label }}</h2><div class="grid gap-3 md:grid-cols-2"><select name="{{ $key }}_verification_status" required>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old($key.'_verification_status',$verification->{$key.'_verification_status'}?->value)===$status->value)>{{ str($status->value)->headline() }}</option>@endforeach</select><input type="date" name="{{ $key }}_verification_date" value="{{ old($key.'_verification_date',$verification->{$key.'_verification_date'}?->format('Y-m-d')) }}"></div></section>@endforeach
<textarea name="remarks" rows="4" placeholder="Verification remarks">{{ old('remarks',$verification->remarks) }}</textarea><button class="btn-primary">Save Verification</button>
</form>
@endsection
