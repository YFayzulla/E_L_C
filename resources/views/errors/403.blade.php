<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Ruxsat Yo'q</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #dc3545;
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container error-container">
        <div>
            <div class="error-code">403</div>
            <div class="error-message">Kechirasiz, sizga bu sahifani ko'rish uchun ruxsat yo'q.</div>
            <p class="text-muted">Agar bu xatolik deb hisoblasangiz, administrator bilan bog'laning.</p>
            <a href="{{ url('/') }}" class="btn btn-primary mt-3">Bosh sahifaga qaytish</a>
            <button onclick="window.history.back()" class="btn btn-secondary mt-3">Ortga qaytish</button>
        </div>
    </div>
</body>
</html>
