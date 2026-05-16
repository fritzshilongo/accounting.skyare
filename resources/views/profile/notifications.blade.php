@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Notifications</h1>
            <p class="hero-copy">Review billing events, system alerts, and operational activity for your workspace.</p>
        </div>
        <a href="/profile" class="btn btn-ghost">Back to Profile</a>
    </div>
</section>

<section class="table-card">
    @if(!empty($notifications))
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notification)
                        @php
                            $isRead = !empty($notification['read_at']);
                        @endphp
                        <tr>
                            <td class="row-title">{{ $notification['title'] ?? 'Notification' }}</td>
                            <td>{{ $notification['message'] ?? '' }}</td>
                            <td><span class="badge {{ $isRead ? 'teal' : 'amber' }}">{{ $isRead ? 'Read' : 'Unread' }}</span></td>
                            <td>{{ $notification['created_at'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">No notifications available.</div>
    @endif
</section>
@endsection