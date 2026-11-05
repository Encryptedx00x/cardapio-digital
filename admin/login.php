<?php require_once __DIR__ . '/../includes/security.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Cardápio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Painel Administrativo</h1>
        <form method="POST" action="login_proc.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
            <input type="email" name="email" placeholder="E-mail" required class="w-full px-3 py-2 border rounded">
            <input type="password" name="senha" placeholder="Senha" required class="w-full px-3 py-2 border rounded">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Entrar</button>
        </form>
        <div class="text-center mt-4">
            <a href="../public/index.php" class="text-sm text-gray-500 hover:underline">Voltar ao site</a>
        </div>
    </div>
</body>
</html>
