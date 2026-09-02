@extends('layouts.admin.layout')

@section('content')
@php
$intervalDisplay = $event->volunteer_reminder_intervals
    ? implode(', ', $event->volunteer_reminder_intervals)
    : implode(', ', json_decode($defaultIntervals, true) ?: []);
$canManage = auth()->user()->usergroup_id == 3
    || auth()->user()->hasPermission('manage-event-volunteers')
    || auth()->user()->hasPermission('update-events');
@endphp

<div class="flex items-center justify-between mb-5">
    <h1 class="admin-h1 flex items-center gap-3">
        <a href="{{ route('admin.events.show', $event->id) }}?tab=volunteers"
            class="rounded-full bg-gray-100 hover:bg-gray-200 p-2 transition">
            <img src="{{ url('uploads/icons/back.svg') }}" class="w-3 h-3">
        </a>
        Event Volunteers — {{ $event->title }}
    </h1>
</div>

@include('partials.message')

@if($canManage)
<div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-5 p-5">
    <h2 class="font-semibold text-gray-700 mb-3">Event Volunteer Settings</h2>
    <form action="{{ route('admin.event.volunteers.settings', $event->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="enable_volunteer_signup" value="1" {{ $event->enable_volunteer_signup ? 'checked' : '' }}>
                Allow member self-signup for open slots
            </label>
        </div>
        <div>
            <label class="tw-form-label">Reminder intervals (minutes, comma-separated)</label>
            <input type="text" name="volunteer_reminder_intervals" value="{{ $intervalDisplay }}"
                class="tw-form-control w-full" placeholder="2880, 1440, 120">
            <p class="text-xs text-gray-400 mt-1">Leave blank to use church defaults.</p>
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary submit-btn text-sm px-4 py-2">Save Settings</button>
        </div>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-5 p-5">
    <h2 class="font-semibold text-gray-700 mb-3">Add Volunteer Job</h2>
    <form action="{{ route('admin.event.volunteers.jobs.store', $event->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="tw-form-label">Job Title<span class="text-red-500">*</span></label>
            <input type="text" name="title" required class="tw-form-control w-full" placeholder="Greeter, Setup, Sound...">
        </div>
        <div>
            <label class="tw-form-label">Slots Needed<span class="text-red-500">*</span></label>
            <input type="number" name="slots_needed" value="1" min="1" max="100" required class="tw-form-control w-full">
        </div>
        <div class="md:col-span-2">
            <label class="tw-form-label">Description</label>
            <textarea name="description" rows="2" class="tw-form-control w-full" placeholder="Optional instructions"></textarea>
        </div>
        <div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="allow_self_signup" value="1" checked>
                Allow self-signup for this job
            </label>
        </div>
        <div>
            <button type="submit" class="btn btn-primary submit-btn text-sm px-4 py-2">Add Job</button>
        </div>
    </form>
</div>
@endif

<div class="space-y-4">
    @forelse($jobs as $job)
    @php
    $activeAssignments = $job->assignments->whereIn('status', ['confirmed', 'pending']);
    @endphp
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">{{ $job->title }}</h3>
                @if($job->description)
                <p class="text-sm text-gray-600 mt-1">{{ $job->description }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-2">
                    <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">
                        {{ $activeAssignments->count() }}/{{ $job->slots_needed }} filled
                    </span>
                    @if($job->allowsSelfSignup())
                    <span class="bg-green-50 text-green-700 px-2 py-0.5 rounded-full ml-1">Self-signup on</span>
                    @else
                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full ml-1">Admin assign only</span>
                    @endif
                </p>
            </div>
            @if($canManage)
            <form action="{{ route('admin.event.volunteers.jobs.destroy', [$event->id, $job->id]) }}" method="POST"
                onsubmit="return confirm('Delete this volunteer job?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete Job</button>
            </form>
            @endif
        </div>

        @if($canManage)
        <form action="{{ route('admin.event.volunteers.jobs.update', [$event->id, $job->id]) }}" method="POST"
            class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 border-t border-gray-100 pt-4">
            @csrf @method('PUT')
            <input type="text" name="title" value="{{ $job->title }}" required class="tw-form-control w-full text-sm">
            <input type="number" name="slots_needed" value="{{ $job->slots_needed }}" min="1" max="100" required class="tw-form-control w-full text-sm">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="allow_self_signup" value="1" {{ $job->allow_self_signup !== false ? 'checked' : '' }}>
                Self-signup
            </label>
            <button type="submit" class="text-sm px-3 py-2 rounded bg-gray-100 hover:bg-gray-200">Update</button>
            <div class="md:col-span-4">
                <textarea name="description" rows="2" class="tw-form-control w-full text-sm" placeholder="Description">{{ $job->description }}</textarea>
            </div>
        </form>
        @endif

        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Assigned Volunteers</h4>
            @forelse($activeAssignments as $assignment)
            <div class="flex items-center justify-between border-b py-2">
                <div>
                    <p class="text-sm font-medium">{{ $assignment->user->fullname ?? $assignment->user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $assignment->user->email }}</p>
                </div>
                @if($canManage)
                <form action="{{ route('admin.event.volunteers.assignments.remove', [$event->id, $job->id, $assignment->id]) }}" method="POST"
                    onsubmit="return confirm('Remove this volunteer?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-600">Remove</button>
                </form>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-400 italic">No volunteers assigned yet.</p>
            @endforelse
        </div>

        @if($canManage && $job->openSlots() > 0)
        <form action="{{ route('admin.event.volunteers.assignments.store', [$event->id, $job->id]) }}" method="POST"
            class="flex flex-col sm:flex-row gap-2">
            @csrf
            <select name="user_id" required class="border border-gray-300 rounded px-3 py-2 text-sm flex-1">
                <option value="">Assign a member…</option>
                @foreach($members as $member)
                @if(!$activeAssignments->contains('user_id', $member->id))
                <option value="{{ $member->id }}">{{ $member->fullname ?? $member->name }}</option>
                @endif
                @endforeach
            </select>
            <button type="submit" class="blue-bg text-white text-sm px-4 py-2 rounded">Assign</button>
        </form>
        @endif
    </div>
    @empty
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-8 text-center">
        <p class="text-gray-400">No volunteer jobs defined for this event yet.</p>
    </div>
    @endforelse
</div>
@endsection
