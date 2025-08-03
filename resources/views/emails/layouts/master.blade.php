<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $title ?? 'Thông báo từ VNUA Schedule System' }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }

        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            box-sizing: border-box;
            color: #333333;
            height: 100%;
            line-height: 1.6;
            width: 100% !important;
            background-color: #f8fafc;
            -webkit-text-size-adjust: none;
        }

        p, ul, ol, blockquote {
            line-height: 1.6;
            text-align: left;
            margin: 0 0 16px;
            color: #3d4852;
        }

        a {
            color: #1e64dc;
            text-decoration: underline;
        }

        a:hover {
            color: #0056b3;
        }

        h1, h2, h3 {
            color: #2d3748;
            margin-top: 0;
            font-weight: bold;
        }

        h1 {
            font-size: 22px;
        }

        h2 {
            font-size: 18px;
        }

        h3 {
            font-size: 16px;
        }

        img {
            max-width: 100%;
        }

        /* Layout */
        .wrapper {
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
        }

        .content {
            margin: 0;
            padding: 0;
            width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 100%;
        }

        .header {
            padding: 25px 0;
            text-align: center;
        }

        .header a {
            font-size: 20px;
            font-weight: bold;
            text-decoration: none;
            color: #43A047;
        }

        .logo {
            height: 40px;
            margin-bottom: 10px;
        }

        .body {
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .inner-body {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
            padding: 0;
            width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 570px;
        }

        .content-cell {
            padding: 35px;
        }

        .content-divider {
            border-top: 1px solid #e8e5ef;
            margin-top: 20px;
            margin-bottom: 20px;
            display: block;
        }

        /* Footer */
        .footer {
            margin: 0 auto;
            padding: 0;
            text-align: center;
            width: 570px;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            -premailer-width: 570px;
        }

        .footer p {
            color: #b0adc5;
            font-size: 12px;
            text-align: center;
        }

        .footer a {
            color: #b0adc5;
            text-decoration: underline;
        }

        /* Buttons */
        .button {
            background-color: #43A047;
            border-radius: 4px;
            color: #ffffff;
            display: inline-block;
            font-weight: bold;
            padding: 10px 20px;
            text-decoration: none;
            -webkit-text-size-adjust: none;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
        }

        .button-primary {
            background-color: #43A047;
            border-color: #43A047;
            color: #ffffff;
        }

        .button-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
            color: #ffffff;
        }

        .button-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
        }

        .button-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #ffffff;
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table td {
            color: #3d4852;
            font-size: 15px;
            line-height: 18px;
            padding: 10px 0;
        }

        .table td:first-child {
            padding-right: 10px;
            width: 35%;
            font-weight: bold;
            color: #555555;
        }

        .event-detail {
            border: 1px solid #e8e5ef;
            border-radius: 4px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fafafa;
        }

        .tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
            color: white;
        }

        .tag-approved {
            background-color: #43A047;
        }

        .tag-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .tag-important {
            background-color: #dc3545;
        }

        .tag-info {
            background-color: #17a2b8;
        }

        .change-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .change-table th {
            background-color: #f8f9fa;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #e8e5ef;
            color: #555555;
        }

        .change-table td {
            padding: 8px;
            border-bottom: 1px solid #e8e5ef;
        }

        .change-table .old-value {
            text-decoration: line-through;
            color: #dc3545;
        }

        .change-table .new-value {
            color: #43A047;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <a href="{{ config('app.url') }}">
                                @if(file_exists(public_path('logo.png')))
                                    <img src="{{ asset('logo.png') }}" class="logo" alt="VNUA Logo">
                                @endif
                                <br>
                                Hệ thống Quản lý Lịch VNUA
                            </a>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td>
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell" align="center">
                                        <p>&copy; {{ date('Y') }} Hệ thống Quản lý Lịch VNUA. Bản quyền thuộc về <a href="https://vnua.edu.vn">Học viện Nông nghiệp Việt Nam</a>.</p>
                                        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
