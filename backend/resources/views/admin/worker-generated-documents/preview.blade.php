@extends('layouts.admin')
@section('title', $document->document_type?->label() ?: 'Worker Document Preview')
@section('heading', $document->document_type?->label() ?: 'Worker Document Preview')
@section('subheading', 'Review the document before generating or sharing it.')
@section('actions')<div class="flex flex-wrap gap-2">@if(!$previewDocument)<form method="POST" action="{{ $document->document_type->value==='worker_resume'?route('admin.workers.resume.generate',$document->worker):route('admin.workers.registration-form.generate',$document->worker) }}">@csrf<button class="btn-primary">Generate PDF</button></form>@else<a class="btn-secondary" target="_blank" href="{{ route('admin.worker-generated-documents.print',$document) }}">Print</a>@endif</div>@endsection
@section('content')<div class="card overflow-hidden bg-neutral-100 p-4"><div class="mx-auto max-w-[850px] bg-white p-3 shadow">@include($document->document_type->value==='worker_resume'?'admin.workers.documents.resume-pdf':'admin.workers.documents.registration-pdf')</div></div>@endsection
