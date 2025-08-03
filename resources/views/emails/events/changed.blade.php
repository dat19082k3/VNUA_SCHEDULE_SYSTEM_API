@extends('emails.layouts.master')

@section('content')
    <h1>Sự kiện đã được cập nhật</h1>

    <p>Kính gửi {{ $notifiable->name }},</p>

    <p>Thông báo sự kiện <strong>"{{ $event->title }}"</strong> đã được cập nhật bởi <strong>{{ $editor->name }}</strong>.
        Các thay đổi như sau:</p>

    <table class="change-table" width="100%" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th width="20%">Trường</th>
                <th width="40%">Giá trị cũ</th>
                <th width="40%">Giá trị mới</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($changes as $field => $change)
                <tr>
                    <td>
                        @if ($field == 'title')
                            Tiêu đề
                        @elseif($field == 'host')
                            Chủ trì
                        @elseif($field == 'start_time')
                            Thời gian bắt đầu
                        @elseif($field == 'end_time')
                            Thời gian kết thúc
                        @elseif($field == 'location')
                            Địa điểm
                        @elseif($field == 'description')
                            Mô tả
                        @else
                            {{ $field }}
                        @endif
                    </td>
                    <td class="old-value">
                        @if (str_contains($field, '_time'))
                            {{ \Carbon\Carbon::parse($change['old_value'])->format('H:i - d/m/Y') }}
                        @else
                            {{ $change['old_value'] }}
                        @endif
                    </td>
                    <td class="new-value">
                        @if (str_contains($field, '_time'))
                            {{ \Carbon\Carbon::parse($change['new_value'])->format('H:i - d/m/Y') }}
                        @else
                            {{ $change['new_value'] }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Dưới đây là thông tin chi tiết của sự kiện sau khi được cập nhật:</p>

    <div class="event-detail">
        <table class="table" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>Tiêu đề:</td>
                <td>{{ $event->title }}</td>
            </tr>
            <tr>
                <td>Chủ trì:</td>
                <td>{{ $event->host ?? 'Chưa xác định' }}</td>
            </tr>
            <tr>
                <td>Thời gian bắt đầu:</td>
                <td>{{ \Carbon\Carbon::parse($event->start_time)->format('H:i - d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Thời gian kết thúc:</td>
                <td>{{ \Carbon\Carbon::parse($event->end_time)->format('H:i - d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Địa điểm:</td>
                <td>
                    @if ($event->locations && $event->locations->count() > 0)
                        @foreach ($event->locations as $location)
                            {{ $location->name }}@if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    @else
                        Chưa xác định
                    @endif
                </td>
            </tr>
            <tr>
                <td>Người chỉnh sửa:</td>
                <td>{{ $editor->name }} ({{ $editor->email }})</td>
            </tr>
            <tr>
                <td>Thời gian chỉnh sửa:</td>
                <td>{{ now()->format('H:i - d/m/Y') }}</td>
            </tr>
        </table>

        <div style="margin-top: 15px">
            <span class="tag tag-info">Đã cập nhật</span>
            @if ($event->is_important)
                <span class="tag tag-important">Quan trọng</span>
            @endif
        </div>
    </div>

    <p>Bạn có thể xem thêm chi tiết sự kiện và thực hiện các thay đổi bổ sung nếu cần thiết bằng cách nhấn vào nút bên dưới:
    </p>

    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td>
                            <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}" class="button button-success"
                                target="_blank">Xem chi tiết sự kiện</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p>Nếu bạn cho rằng có sai sót hoặc cần hỗ trợ, vui lòng liên hệ với người chỉnh sửa hoặc quản trị viên hệ thống.</p>

    <p>Trân trọng,<br>
        Hệ thống Quản lý Lịch VNUA</p>
@endsection
