@props(['code', 'message'])
{{-- Standalone on purpose: must render even when the database is down. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $message }}</title>
    <style>body{font-family:system-ui,sans-serif;display:grid;place-content:center;min-height:100vh;margin:0;padding:16px;text-align:center;background:#fafaf9;color:#1c1917}a{color:#3f2878;font-weight:700}</style>
</head>
<body>
    <h1>{{ $code }}</h1>
    <p>{{ $message }}</p>
    <p><a href="/">العودة إلى الرئيسية</a></p>
</body>
</html>
