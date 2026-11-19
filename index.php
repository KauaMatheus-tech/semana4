<?php
session_start();
require_once 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario'] = $usuario['nome'];
            header("Location: dashboard.php");
            exit();
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechStore - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #030b17;
            --bg-card: rgba(10, 25, 47, 0.75);
            --cyan: #00e5ff;
            --cyan-hover: #00b8d4;
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --border-glow: rgba(0, 229, 255, 0.35);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            background: radial-gradient(circle at 20% 30%, #091e3a 0%, var(--bg-dark) 70%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: 100%; max-width: 1150px;
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 40px; padding: 20px; align-items: center;
        }
        .brand-section { display: flex; flex-direction: column; align-items: center; text-align: center; }
        .logo-icon { font-size: 4.5rem; color: var(--cyan); filter: drop-shadow(0 0 15px rgba(0, 229, 255, 0.6)); margin-bottom: 10px; }
        .brand-title { font-size: 3.5rem; font-weight: 800; font-style: italic; }
        .brand-title span { color: var(--cyan); }
        .brand-subtitle { color: #e2e8f0; font-size: 1.15rem; margin-top: 5px; margin-bottom: 25px; }
        .divider-glow { width: 120px; height: 2px; background: linear-gradient(90deg, transparent, var(--cyan), transparent); margin-bottom: 30px; }
        .features { display: flex; flex-direction: column; gap: 18px; text-align: left; margin-bottom: 40px; }
        .feature-item { display: flex; align-items: center; gap: 15px; font-size: 1.05rem; }
        .feature-item i { color: var(--cyan); font-size: 1.4rem; }
        .login-card {
            background: var(--bg-card); border: 1px solid var(--border-glow);
            border-radius: 20px; padding: 45px 40px; backdrop-filter: blur(12px);
            box-shadow: 0 0 35px rgba(0, 229, 255, 0.12); max-width: 460px; width: 100%; margin: 0 auto;
        }
        .avatar-circle {
            width: 82px; height: 82px; border-radius: 50%; border: 2px solid var(--cyan);
            display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;
            color: var(--cyan); font-size: 2.4rem; box-shadow: 0 0 20px rgba(0, 229, 255, 0.35);
        }
        .login-card h2 { text-align: center; font-size: 1.9rem; margin-bottom: 6px; }
        .login-card p.sub { text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 28px; }
        .error-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; text-align: center; }
        .input-group { position: relative; margin-bottom: 18px; }
        .input-group i.left-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .input-group input {
            width: 100%; padding: 14px 44px; background: rgba(15, 31, 56, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.25); border-radius: 10px; color: #fff; font-size: 0.95rem; outline: none;
        }
        .input-group input:focus { border-color: var(--cyan); box-shadow: 0 0 12px rgba(0, 229, 255, 0.25); }
        .options-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 24px; }
        .options-row label { display: flex; align-items: center; gap: 8px; cursor: pointer; color: #e2e8f0; }
        .options-row input[type="checkbox"] { accent-color: var(--cyan); width: 16px; height: 16px; }
        .options-row a { color: var(--cyan); text-decoration: none; }
        .btn-primary {
            width: 100%; padding: 14px; background: var(--cyan); color: #030b17; border: none;
            border-radius: 10px; font-size: 1.05rem; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.4); transition: 0.3s;
        }
        .btn-primary:hover { background: var(--cyan-hover); }
        .signup-text { text-align: center; margin-top: 24px; font-size: 0.9rem; color: #cbd5e1; }
        .signup-text a { color: var(--cyan); font-weight: 600; text-decoration: none; }
        @media (max-width: 860px) { .container { grid-template-columns: 1fr; } .brand-section { display: none; } }
    </style>
</head>
<body>
<div class="container">
    <div class="brand-section">
        <div class="logo-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <h1 class="brand-title">Tech<span>Store</span></h1>
        <p class="brand-subtitle">Tecnologia ao seu alcance</p>
        <div class="divider-glow"></div>
        <div class="features">
            <div class="feature-item"><i class="fa-solid fa-shield-halved"></i><span>Compra segura</span></div>
            <div class="feature-item"><i class="fa-solid fa-bolt"></i><span>Entrega rápida</span></div>
            <div class="feature-item"><i class="fa-solid fa-headset"></i><span>Suporte especializado</span></div>
        </div>
    </div>

    <div class="login-card">
        <div class="avatar-circle"><i class="fa-regular fa-user"></i></div>
        <h2>Bem-vindo(a)!</h2>
        <p class="sub">Faça login para continuar</p>

        <?php if ($erro): ?>
            <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <i class="fa-regular fa-user left-icon"></i>
                <input type="email" name="usuario" placeholder="Seu e-mail cadastrado" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock left-icon"></i>
                <input type="password" name="senha" placeholder="Sua senha" required>
            </div>
            <div class="options-row">
                <label><input type="checkbox" checked> Lembrar-me</label>
                <a href="#">Esqueceu sua senha?</a>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-right-to-bracket"></i> Entrar
            </button>
        </form>

        <p class="signup-text">
            Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
        </p>
    </div>
</div>
</body>
</html>