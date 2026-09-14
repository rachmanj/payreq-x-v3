<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print OP Error</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 40px;
            background: #fff5f5;
            color: #7f1d1d;
        }
        .error-box {
            max-width: 720px;
            margin: 0 auto;
            border: 1px solid #fecaca;
            background: #fff;
            border-radius: 8px;
            padding: 24px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 12px;
        }
        p {
            margin: 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Tidak dapat menampilkan Cash Bank Voucher Out</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
