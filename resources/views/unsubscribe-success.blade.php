@extends('layouts.guest')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Unsubscribed Successfully</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>
                    <h5 class="mb-3">You have been unsubscribed</h5>
                    @if(session('email'))
                        <p class="mb-4">The email address <strong>{{ session('email') }}</strong> has been removed from our mailing list.</p>
                    @endif
                    <p class="text-muted">You will no longer receive emails from us.</p>
                    <a href="/" class="btn btn-primary mt-3">Go to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
