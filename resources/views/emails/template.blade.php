@extends('emails.layout')

@section('content')
{!! $corps !!}

@if(!empty($signature_name) || !empty($signature_phone))
<div style="margin-top:2rem; border-top:1px solid #e5e7eb; padding-top:1rem; color:#4b5563; font-size:14px;">
    @if(!empty($signature_name))
        <div style="font-weight:600; margin-bottom:0.25rem;">{{ $signature_name }}</div>
    @endif
    @if(!empty($signature_phone))
        <div>Tél. {{ $signature_phone }}</div>
    @endif
</div>
@endif
@endsection
