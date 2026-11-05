<?php require_once __DIR__ . '/../includes/security.php'; ?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Entrar | Cardápio</title><link rel="stylesheet" href="style.css"></head><body>
<main class="container narrow"><a href="index.php">← Cardápio público</a><h1>Entrar na sua conta</h1>
<?php if (isset($_GET['registered'])): ?><p class="notice">Cadastro concluído. Entre para continuar.</p><?php endif; ?>
<form method="post" action="../api/login.php" class="stack"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><label>E-mail<input type="email" name="email" autocomplete="email" required></label><label>Senha<input type="password" name="senha" autocomplete="current-password" required></label><button>Entrar</button></form>
<p>Não tem conta? <a href="register.php">Cadastre-se</a></p></main></body></html>
