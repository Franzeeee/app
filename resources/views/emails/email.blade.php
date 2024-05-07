<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Circular+Std">
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="{{ asset('img/Cassette-logo.png') }}" alt="Logo">
            <h2>Cassette</h2>
        </div>
        <p>Please verify your email address to complete the registration process.</p>
        <a href="{{ url($verificationUrl) }}" class="verify-btn">Confirm Email</a>
    </div>
</body>
</html>
