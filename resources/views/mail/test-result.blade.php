<!DOCTYPE html>
<html>
<head>
    <title>Mail Test Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
            max-height: 300px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kết quả test gửi mail</h1>

        <div class="alert {{ str_contains(strtolower($output), 'error') ? 'alert-danger' : 'alert-success' }}">
            <pre>{{ $output }}</pre>
        </div>

        <h2>Thông tin log file</h2>
        @if(isset($logInfo))
            <ul>
                <li>Log file: <code>{{ $logInfo['path'] }}</code></li>
                <li>Tồn tại: <strong>{{ $logInfo['exists'] ? 'Có' : 'Không' }}</strong></li>
                <li>Kích thước: <strong>{{ round($logInfo['size'] / 1024, 2) }} KB</strong></li>
                <li>Lần cuối chỉnh sửa: <strong>{{ $logInfo['last_modified'] ? date('Y-m-d H:i:s', $logInfo['last_modified']) : 'N/A' }}</strong></li>
            </ul>

            @if(!empty($logInfo['recent_logs']))
                <h3>Logs gần đây:</h3>
                <pre>{{ implode("", $logInfo['recent_logs']) }}</pre>
            @else
                <p>Không tìm thấy log gần đây.</p>
            @endif
        @endif

        <p><i>Lưu ý: Nếu đang sử dụng queue, thông báo sẽ được xử lý trong background và có thể chưa được gửi ngay lập tức.
        Chạy <code>php artisan queue:work</code> để xử lý các email trong queue.</i></p>
    </div>
</body>
</html>
