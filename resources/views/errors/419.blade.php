@extends('layouts.app')

@section('title', 'Page Expired')

@section('content')
<div class="alert alert-danger" style="max-width: 560px; margin: 60px auto;">
    <h2>419 | Page Expired</h2>
    <p>Your session has expired. Please reload and try again.</p>
    <p><a class="btn btn-primary" href="/login">Go to Login</a></p>
</div>
@endsection