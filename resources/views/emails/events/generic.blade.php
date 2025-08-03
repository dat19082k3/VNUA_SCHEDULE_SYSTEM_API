@extends('emails.layouts.master')

@section('content')
    <h1>{{ $title ?? 'Thông báo sự kiện' }}</h1>

    <p>Kính gửi {{ $notifiable->name }},</p>

    <p>{{ $message ?? 'Có thông báo mới liên quan đến sự kiện trong hệ thống.' }}</p>

    @if (isset($event))
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
                @if ($event->locations && $event->locations->count() > 0)
                    <tr>
                        <td>Địa điểm:</td>
                        <td>
                            @foreach ($event->locations as $location)
                                {{ $location->name }}@if (!$loop->last)
                                    ,
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endif
                @if (isset($event->description) && !empty($event->description))
                    <tr>
                        <td>Mô tả:</td>
                        <td>{{ $event->description }}</td>
                    </tr>
                @endif
                @if (isset($event->preparers) && $event->preparers->count() > 0)
                    <tr>
                        <td>Đơn vị chuẩn bị:</td>
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
                @if (isset($tags) && is_array($tags))
                    @foreach ($tags as $tag => $label)
                        <span class="tag tag-{{ $tag }}">{{ $label }}</span>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    @if (isset($additionalContent))
        {!! $additionalContent !!}
    @endif

    @if (isset($event))
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
    @endif

    <p>{{ $footer ?? 'Nếu bạn cần hỗ trợ hoặc có bất kỳ thắc mắc nào, vui lòng liên hệ với quản trị viên hệ thống.' }}</p>

    <p>Trân trọng,<br>
        Hệ thống Quản lý Lịch VNUA</p>
@endsection
