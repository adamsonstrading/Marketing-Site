@extends('layouts.guest')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Unsubscribe</h4>
                </div>
                <div class="card-body">
                    <p class="mb-4">You are about to unsubscribe <strong>{{ $email }}</strong> from our mailing list.</p>
                    <p class="mb-4">This will stop all future emails to this address.</p>
                    
                    <form method="POST" action="{{ route('unsubscribe.process') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-ban"></i> Unsubscribe
                            </button>
                            <a href="/" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
