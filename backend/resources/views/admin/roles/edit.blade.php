@extends('layouts.admin')
@section('title','Edit '.$role->name) @section('heading','Edit '.$role->name) @section('subheading','Permission changes apply to all active users assigned this role.')
@section('content')
<form method="POST" action="{{ route('admin.roles.update',$role) }}">@csrf @method('PUT')
<div class="card mb-6 p-6"><label for="description">Description</label><textarea id="description" name="description" rows="3">{{ old('description',$role->description) }}</textarea></div>
<div class="grid gap-5 md:grid-cols-2">@foreach($permissionGroups as $module=>$permissions)<section class="card p-6"><h2 class="mb-4 text-base font-black">{{ $module }}</h2><div class="space-y-3">@foreach($permissions as $permission)<label class="flex items-start gap-3 rounded-xl border border-neutral-100 p-3 font-normal hover:bg-neutral-50"><input class="mt-0.5 h-4 w-4" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id,old('permissions',$role->permissions->pluck('id')->all())))><span><strong class="block text-sm">{{ $permission->name }}</strong><small class="font-mono text-neutral-400">{{ $permission->slug }}</small></span></label>@endforeach</div></section>@endforeach</div>
<div class="mt-6 flex gap-3"><button class="btn-primary">Save permissions</button><a class="btn-secondary" href="{{ route('admin.roles.index') }}">Cancel</a></div></form>
@endsection
