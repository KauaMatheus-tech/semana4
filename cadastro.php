<?php
session_start();
require_once __DIR__ . '/conexao.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 8) {
        $erro = 'Informe nome e e-mail válidos. A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não coincidem.';
    } else {
        $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
            $insert->execute([$nome, $email, $hash]);
            $_SESSION['usuario_id'] = (int)$pdo->lastInsertId();
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_email'] = $email;
            session_regenerate_id(true);
            header('Location: dashboard.php'); exit;
        }
    }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cadastro | TechStore</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-[#020b18] text-white flex items-center justify-center p-4"><div class="w-full max-w-md rounded-2xl border border-cyan-700 bg-[#06172b] p-8 shadow-xl">
<h1 class="text-3xl font-bold mb-2">Criar conta <span class="text-cyan-300">TechStore</span></h1><p class="text-slate-400 mb-6">Preencha os dados para se cadastrar.</p>
<?php if($erro): ?><p class="bg-red-950 border border-red-700 text-red-200 p-3 rounded mb-4"><?=htmlspecialchars($erro,ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<form method="post" class="space-y-4">
<label class="block text-sm">Nome completo<input name="nome" required class="block w-full mt-1 rounded-lg bg-[#071a30] border border-cyan-900 p-3"></label>
<label class="block text-sm">E-mail<input name="email" type="email" required class="block w-full mt-1 rounded-lg bg-[#071a30] border border-cyan-900 p-3"></label>
<label class="block text-sm">Senha (mínimo 8 caracteres)<input name="senha" type="password" minlength="8" required class="block w-full mt-1 rounded-lg bg-[#071a30] border border-cyan-900 p-3"></label>
<label class="block text-sm">Confirmar senha<input name="confirmar" type="password" minlength="8" required class="block w-full mt-1 rounded-lg bg-[#071a30] border border-cyan-900 p-3"></label>
<button class="w-full rounded-lg bg-cyan-400 text-slate-950 font-bold p-3">Cadastrar</button>
</form><p class="mt-5 text-sm text-slate-400 text-center">Já tem conta? <a href="login.php" class="text-cyan-300">Entrar</a></p></div></body></html>
