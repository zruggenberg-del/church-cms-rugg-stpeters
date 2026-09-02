@extends('layouts.admin.layout')

@section('content')
<div class="py-5 bg-white shadow px-3">
    @include('partials.message')

    <h1 class="admin-h1 mb-4">Volunteer Settings</h1>

    <form action="{{ url('/admin/settings/volunteer') }}" method="POST" class="max-w-2xl">
        @csrf
        <div class="mb-4">
            <label class="tw-form-label">Default reminder intervals (minutes, comma-separated)</label>
            <input type="text" name="volunteer_reminder_intervals" value="{{ is_string($intervals) ? $intervals : json_encode($intervals) }}"
                class="tw-form-control w-full" required placeholder="10080, 1440, 120">
            <p class="text-xs text-gray-400 mt-1">Examples: 10080 = 7 days, 1440 = 1 day, 120 = 2 hours before the event.</p>
        </div>

        <div class="mb-6">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="volunteer_default_self_signup" value="1" {{ $defaultSelfSignup ? 'checked' : '' }}>
                Enable self-signup by default for new volunteer jobs
            </label>
        </div>

        <button type="submit" class="btn btn-primary submit-btn text-sm px-4 py-2">Save Settings</button>
    </form>
</div>
@endsection
