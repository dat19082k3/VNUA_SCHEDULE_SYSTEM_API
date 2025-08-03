@extends('emails.layouts.master')

@section('content')
    <h1>Sự kiện đã được phê duyệt</h1>

    <p>Kính gửi {{ $notifiable->name }},</p>

    <p>Sự kiện <strong>"{{ $event->title }}"</strong> của bạn đã được phê duyệt và đã được thêm vào lịch. Chi tiết sự kiện
        như sau:</p>

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
                <td>Mô tả:</td>
                <td>{{ $event->description ?? 'Không có mô tả' }}</td>
            </tr>
            @if ($event->preparers && $event->preparers->count() > 0)
                <tr>
                    <td>Người chuẩn bị:</td>
                    <td>
                        @foreach ($event->preparers as $preparer)
                            {{ $preparer->name }}@if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    </td>
                </tr>
            @endif
        </table>

        <div style="margin-top: 15px">
            <span class="tag tag-approved">Đã phê duyệt</span>
            @if ($event->is_important)
                <span class="tag tag-important">Quan trọng</span>
            @endif
        </div>
    </div>

    <p>Bạn có thể xem thêm chi tiết sự kiện và thực hiện các thay đổi nếu cần thiết bằng cách nhấn vào nút bên dưới:</p>

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

    <p>Nếu bạn cần hỗ trợ hoặc có bất kỳ thắc mắc nào, vui lòng liên hệ với quản trị viên hệ thống.</p>

    <p>Trân trọng,<br>
        Hệ thống Quản lý Lịch VNUA</p>
@endsection
