@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
<div class="container py-5" style="max-width: 960px;">
    <h1 class="h2 mb-2">Contact Us</h1>
    <p class="text-gasq-muted mb-4">Questions about GASQ, your account, or pricing? Send us a message and we&rsquo;ll get back to you.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card gasq-card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('contact.submit') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror">
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card gasq-card">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Reach us directly</h2>
                    <p class="mb-1"><i class="fa fa-envelope me-2 text-gasq-muted"></i><a href="mailto:info@getasecurityquotenow.com" class="text-decoration-none">info@getasecurityquotenow.com</a></p>
                    <p class="mb-1"><i class="fa fa-phone me-2 text-gasq-muted"></i>P: (470) 633-2816</p>
                    <p class="mb-1"><i class="fa fa-phone me-2 text-gasq-muted"></i>A: (404) 922-2872</p>
                    <p class="text-gasq-muted small mb-3">(open 24 hours a day, 7 days a week)</p>
                    <a href="https://getasecurityquote.bookafy.com/" target="_blank" rel="noopener" class="btn btn-dark btn-sm rounded-pill">
                        <i class="fa fa-phone-alt me-2"></i>Book a Call
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
