<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal</title>
</head>

<body class="flex items-center justify-center h-screen bg-gray-100">

    <div class="text-center">
        <h1 class="mb-6 text-4xl font-bold text-gray-800">Bienvenido a mi Proyecto 🚀</h1>
        <a href="{{ route('filament.powerCheck.auth.login') }}"
            class="px-6 py-3 text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
            Ir al Login
        </a>
    </div>

</body>

</html>
