<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$nomeUsuario = $_SESSION['usuario'];

// Filtro de Categoria e Busca
$categoriaAtiva = $_GET['cat'] ?? 'Todos';
$busca = trim($_GET['busca'] ?? '');

// Consulta produtos do banco
$sql = "SELECT p.*, c.nome AS categoria FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";
$params = [];

if ($categoriaAtiva !== 'Todos') {
    $sql .= " AND c.nome = :cat";
    $params['cat'] = $categoriaAtiva;
}

if ($busca !== '') {
    $sql .= " AND p.nome LIKE :busca";
    $params['busca'] = "%$busca%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Ações do Carrinho
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $acao = $_GET['acao'];

    if ($acao === 'add') {
        $stmtProd = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmtProd->execute([$id]);
        $prod = $stmtProd->fetch();

        if ($prod) {
            if (!isset($_SESSION['carrinho'])) {
                $_SESSION['carrinho'] = [];
            }
            if (isset($_SESSION['carrinho'][$id])) {
                $_SESSION['carrinho'][$id]['qtd']++;
            } else {
                $_SESSION['carrinho'][$id] = [
                    'id'    => $prod['id'],
                    'nome'  => $prod['nome'],
                    'cor'   => $prod['cor'],
                    'preco' => $prod['preco'],
                    'qtd'   => 1,
                    'img'   => $prod['imagem_url']
                ];
            }
        }
    } elseif ($acao === 'inc' && isset($_SESSION['carrinho'][$id])) {
        $_SESSION['carrinho'][$id]['qtd']++;
    } elseif ($acao === 'dec' && isset($_SESSION['carrinho'][$id])) {
        $_SESSION['carrinho'][$id]['qtd']--;
        if ($_SESSION['carrinho'][$id]['qtd'] <= 0) {
            unset($_SESSION['carrinho'][$id]);
        }
    } elseif ($acao === 'del' && isset($_SESSION['carrinho'][$id])) {
        unset($_SESSION['carrinho'][$id]);
    }

    $redirect = "dashboard.php";
    $query = [];
    if ($categoriaAtiva !== 'Todos') {
        $query['cat'] = $categoriaAtiva;
    }
    if ($busca !== '') {
        $query['busca'] = $busca;
    }
    if (!empty($query)) {
        $redirect .= "?" . http_build_query($query);
    }

    header("Location: " . $redirect);
    exit();
}

$carrinho = $_SESSION['carrinho'] ?? [];
$totalItens = 0;
$subtotal = 0;

foreach ($carrinho as $item) {
    $totalItens += (int)$item['qtd'];
    $subtotal += ((float)$item['preco'] * (int)$item['qtd']);
}

$frete = ($subtotal >= 199 || $subtotal == 0) ? 0 : 29.90;
$totalGeral = $subtotal + $frete;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>TechStore - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #030b17; --bg-panel: #071326; --bg-card: #0a1930;
            --cyan: #00e5ff; --text-white: #ffffff; --text-muted: #94a3b8; --border-cyan: rgba(0, 229, 255, 0.3);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-white); display: grid; grid-template-columns: 240px 1fr 340px; min-height: 100vh; gap: 18px; padding: 16px; }
        .sidebar { background: #07162c; border: 1px solid var(--border-cyan); border-radius: 18px; padding: 22px 16px; display: flex; flex-direction: column; justify-content: space-between; height: calc(100vh - 32px); position: sticky; top: 16px; }
        .logo-box { display: flex; align-items: center; gap: 10px; margin-bottom: 30px; }
        .logo-box i { font-size: 1.8rem; color: var(--cyan); }
        .logo-box h1 { font-size: 1.4rem; font-style: italic; font-weight: 800; }
        .logo-box h1 span { color: var(--cyan); }
        .nav-menu { display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 10px; color: #cbd5e1; text-decoration: none; font-size: 0.92rem; }
        .nav-item div { display: flex; align-items: center; gap: 12px; }
        .nav-item.active, .nav-item:hover { background: rgba(0, 229, 255, 0.2); border-left: 3px solid var(--cyan); color: #fff; }
        .badge-cyan { background: var(--cyan); color: #030b17; font-weight: 700; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; }
        .main-content { display: flex; flex-direction: column; gap: 20px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .search-bar { flex: 1; max-width: 440px; position: relative; }
        .search-bar input { width: 100%; background: var(--bg-panel); border: 1px solid var(--border-cyan); border-radius: 10px; padding: 11px 16px 11px 40px; color: #fff; outline: none; }
        .search-bar i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--cyan); }
        .alert-error { background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.45); color: #fca5a5; padding: 12px 16px; border-radius: 12px; font-size: 0.88rem; }
        .hero-banner { background: linear-gradient(110deg, #071a36 0%, #093566 60%, #0077b6 100%); border: 1px solid var(--border-cyan); border-radius: 18px; padding: 28px 34px; display: flex; justify-content: space-between; align-items: center; }
        .hero-text h2 { font-size: 2rem; margin: 8px 0 12px; }
        .categories { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; }
        .cat-card { background: var(--bg-panel); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 12px; padding: 14px 8px; text-align: center; text-decoration: none; color: #cbd5e1; font-size: 0.85rem; }
        .cat-card.active, .cat-card:hover { background: var(--cyan); color: #030b17; border-color: var(--cyan); font-weight: 600; }
        .cat-card i { color: var(--cyan); display: block; margin-bottom: 6px; font-size: 1.1rem; }
        .cat-card.active i, .cat-card:hover i { color: #030b17; }
        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .product-card { background: var(--bg-panel); border: 1px solid var(--border-cyan); border-radius: 14px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; }
        .product-card img { width: 100%; height: 115px; object-fit: contain; border-radius: 8px; margin-bottom: 10px; }
        .product-card h4 { font-size: 0.92rem; margin-bottom: 6px; }
        .price { font-size: 1.05rem; font-weight: 800; margin-bottom: 10px; color: var(--cyan); }
        .btn-buy { flex: 1; background: var(--cyan); color: #030b17; border-radius: 8px; padding: 8px; font-weight: 700; text-align: center; text-decoration: none; font-size: 0.8rem; }
        .right-panel { display: flex; flex-direction: column; gap: 16px; }
        .widget-box { background: var(--bg-panel); border: 1px solid var(--border-cyan); border-radius: 18px; padding: 18px; position: sticky; top: 16px; }
        .cart-item { display: flex; align-items: center; gap: 10px; background: var(--bg-card); padding: 10px; border-radius: 12px; margin-bottom: 10px; }
        .cart-item img { width: 46px; height: 46px; object-fit: cover; border-radius: 8px; }
        .qty-controls { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
        .qty-btn { background: rgba(0, 229, 255, 0.15); color: var(--cyan); border: 1px solid var(--border-cyan); width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 700; font-size: 0.8rem; }
        .summary-line { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-muted); margin-top: 6px; }
        .btn-checkout { width: 100%; background: var(--cyan); color: #030b17; border: none; border-radius: 10px; padding: 12px; font-weight: 700; cursor: pointer; margin-top: 14px; }
        .logout-btn { color: #f87171; text-decoration: none; }
        @media (max-width: 1180px) {
            body { grid-template-columns: 1fr; }
            .sidebar, .widget-box { position: static; height: auto; }
            .categories { grid-template-columns: repeat(4, 1fr); }
            .products-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div>
            <div class="logo-box">
                <i class="fa-solid fa-cart-shopping"></i>
                <div>
                    <h1>Tech<span>Store</span></h1>
                    <small style="color:var(--text-muted)">Tecnologia</small>
                </div>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item active"><div><i class="fa-solid fa-house"></i> Início</div></a>
                <a href="#carrinho" class="nav-item"><div><i class="fa-solid fa-cart-shopping"></i> Carrinho</div><span class="badge-cyan"><?= $totalItens ?></span></a>
                <a href="pedidos.php" class="nav-item"><div><i class="fa-solid fa-box"></i> Meus Pedidos</div></a>
            </nav>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(148,163,184,0.15); padding-top:14px;">
            <div><strong><?= htmlspecialchars($nomeUsuario) ?></strong></div>
            <a href="logout.php" class="logout-btn" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <form method="GET" class="search-bar">
                <?php if ($categoriaAtiva !== 'Todos'): ?>
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($categoriaAtiva) ?>">
                <?php endif; ?>
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="busca" placeholder="O que você está procurando?" value="<?= htmlspecialchars($busca) ?>">
            </form>
        </header>

        <?php if (!empty($_SESSION['erro_pedido'])): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['erro_pedido']) ?>
            </div>
            <?php unset($_SESSION['erro_pedido']); ?>
        <?php endif; ?>

        <section class="hero-banner">
            <div class="hero-text">
                <span style="color:var(--cyan); font-size:0.78rem; font-weight:700;">LANÇAMENTOS</span>
                <h2>Tecnologia que te acompanha</h2>
                <p style="color:#cbd5e1; font-size:0.9rem;">Os melhores produtos do mercado com total segurança.</p>
            </div>
            <i class="fa-solid fa-headphones" style="font-size: 4rem; color: #00e5ff;"></i>
        </section>

        <section class="categories">
            <?php
            $cats = [
                'Todos'       => 'fa-border-all',
                'Smartphones' => 'fa-mobile-screen-button',
                'Notebooks'   => 'fa-laptop',
                'Fones'       => 'fa-headphones',
                'Smart TV'    => 'fa-tv',
                'Games'       => 'fa-gamepad',
                'Acessórios'  => 'fa-clock'
            ];
            foreach ($cats as $nomeCat => $icone):
                $ativo = ($categoriaAtiva === $nomeCat) ? 'active' : '';
            ?>
                <a href="?cat=<?= urlencode($nomeCat) ?>" class="cat-card <?= $ativo ?>">
                    <i class="fa-solid <?= $icone ?>"></i>
                    <span><?= htmlspecialchars($nomeCat) ?></span>
                </a>
            <?php endforeach; ?>
        </section>

        <section>
            <h3 style="margin-bottom:12px;">Produtos em destaque</h3>
            <div class="products-grid">
                <?php if (empty($produtos)): ?>
                    <p style="color:var(--text-muted); grid-column: 1 / -1;">Nenhum produto encontrado.</p>
                <?php else: ?>
                    <?php foreach ($produtos as $prod): ?>
                        <div class="product-card">
                            <img src="<?= htmlspecialchars($prod['imagem_url']) ?>" alt="<?= htmlspecialchars($prod['nome']) ?>">
                            <div>
                                <h4><?= htmlspecialchars($prod['nome']) ?></h4>
                                <div class="price">R$ <?= number_format((float)$prod['preco'], 2, ',', '.') ?></div>
                                <div style="display:flex; gap:6px;">
                                    <a href="?acao=add&id=<?= (int)$prod['id'] ?>" class="btn-buy">Comprar agora</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <aside class="right-panel">
        <div id="carrinho" class="widget-box">
            <div style="font-weight:700; margin-bottom:16px;"><i class="fa-solid fa-cart-shopping" style="color:var(--cyan)"></i> Carrinho</div>
            <?php if (empty($carrinho)): ?>
                <p style="color:var(--text-muted); font-size:0.85rem; text-align:center;">Carrinho vazio.</p>
            <?php else: ?>
                <?php foreach ($carrinho as $item): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>">
                        <div style="flex:1;">
                            <h5 style="font-size:0.84rem;"><?= htmlspecialchars($item['nome']) ?></h5>
                            <strong style="font-size:0.85rem; color:var(--cyan);">R$ <?= number_format((float)$item['preco'], 2, ',', '.') ?></strong>
                            <div class="qty-controls">
                                <a href="?acao=dec&id=<?= (int)$item['id'] ?>" class="qty-btn">-</a>
                                <span style="font-size:0.82rem; font-weight:600;"><?= (int)$item['qtd'] ?></span>
                                <a href="?acao=inc&id=<?= (int)$item['id'] ?>" class="qty-btn">+</a>
                            </div>
                        </div>
                        <a href="?acao=del&id=<?= (int)$item['id'] ?>" style="color:#f87171;"><i class="fa-regular fa-trash-can"></i></a>
                    </div>
                <?php endforeach; ?>

                <div style="border-top: 1px solid rgba(148,163,184,0.15); padding-top: 10px; margin-top: 10px;">
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Frete:</span>
                        <span><?= $frete == 0 ? '<span style="color:#4ade80;">Grátis</span>' : 'R$ ' . number_format($frete, 2, ',', '.') ?></span>
                    </div>
                    <div style="margin-top:10px; font-weight:800; display:flex; justify-content:space-between; font-size:1.05rem;">
                        <span>Total:</span>
                        <span style="color:var(--cyan);">R$ <?= number_format($totalGeral, 2, ',', '.') ?></span>
                    </div>
                </div>

                <form action="finalizar_pedido.php" method="POST">
                    <button type="submit" class="btn-checkout">Finalizar Compra</button>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</body>
</html>