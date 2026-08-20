@extends('emails.layout')
@section('content')
<div style="font-family:Arial,Helvetica,sans-serif; color:#1f2937; font-size:15px; line-height:1.65;">
    {!! $corps !!}

    @if(!empty($signature_name) || !empty($signature_phone) || !empty($signature_email))
        <div style="margin-top:28px; padding-top:14px; border-top:1px solid #e5e7eb; color:#4b5563; font-size:14px; line-height:1.5;">
            @if(!empty($signature_name))<div style="font-weight:600;">{{ $signature_name }}</div>@endif
            @if(!empty($signature_phone))<div>Tél. {{ $signature_phone }}</div>@endif
            @if(!empty($signature_email))<div><a href="mailto:{{ $signature_email }}" style="color:#2563eb;">{{ $signature_email }}</a></div>@endif
        </div>
    @endif
</div>
@endsection
