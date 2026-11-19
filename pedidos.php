<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$usuarioId = (int)$_SESSION['usuario_id'];
$nomeUsuario = $_SESSION['usuario'] ?? 'Cliente';
$totalItensCarrinho = array_sum(array_column($_SESSION['carrinho'] ?? [], 'qtd'));

$etapas = ['Confirmado', 'Em separação', 'A caminho', 'Entregue'];

// Ação para avançar ou alterar o status do pedido no banco
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'])) {
    $pedidoIdPost = (int)$_POST['pedido_id'];
    $acaoStatus = $_POST['acao_status'] ?? 'avancar';

    $stmtBusca = $pdo->prepare("SELECT id, status FROM pedidos WHERE id = :pid AND usuario_id = :uid");
    $stmtBusca->execute(['pid' => $pedidoIdPost, 'uid' => $usuarioId]);
    $pedidoAlvo = $stmtBusca->fetch();

    if ($pedidoAlvo) {
        $idxAtual = array_search($pedidoAlvo['status'], $etapas);
        if ($idxAtual === false) { $idxAtual = 0; }

        if ($acaoStatus === 'avancar') {
            $novoIdx = min($idxAtual + 1, count($etapas) - 1);
            $novoStatus = $etapas[$novoIdx];
        } else {
            $statusEscolhido = $_POST['novo_status'] ?? '';
            $novoStatus = in_array($statusEscolhido, $etapas, true) ? $statusEscolhido : $pedidoAlvo['status'];
        }

        $stmtUpd = $pdo->prepare("UPDATE pedidos SET status = :status WHERE id = :pid AND usuario_id = :uid");
        $stmtUpd->execute([
            'status' => $novoStatus,
            'pid' => $pedidoIdPost,
            'uid' => $usuarioId
        ]);
    }

    header("Location: pedidos.php?acompanhar=" . $pedidoIdPost . "&status_atualizado=1");
    exit();
}

// Busca os pedidos do usuário
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = :uid ORDER BY criado_em DESC, id DESC");
$stmt->execute(['uid' => $usuarioId]);
$pedidos = $stmt->fetchAll();

// Pedido selecionado para acompanhamento detalhado
$pedidoSelecionadoId = isset($_GET['acompanhar']) ? (int)$_GET['acompanhar'] : (!empty($pedidos) ? (int)$pedidos[0]['id'] : 0);
$pedidoDetalhe = null;
$itensPedido = [];

foreach ($pedidos as $p) {
    if ((int)$p['id'] === $pedidoSelecionadoId) {
        $pedidoDetalhe = $p;
        break;
    }
}

if (!$pedidoDetalhe && !empty($pedidos)) {
    $pedidoDetalhe = $pedidos[0];
}

if ($pedidoDetalhe) {
    $stmtItens = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = :pid");
    $stmtItens->execute(['pid' => $pedidoDetalhe['id']]);
    $itensPedido = $stmtItens->fetchAll();
}

$indiceAtual = $pedidoDetalhe ? array_search($pedidoDetalhe['status'], $etapas) : 0;
if ($indiceAtual === false) { $indiceAtual = 0; }
$jaEntregue = ($indiceAtual >= count($etapas) - 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>TechStore - Meus Pedidos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #030b17; --bg-panel: #071326; --bg-card: #0a1930;
            --cyan: #00e5ff; --text-white: #ffffff; --text-muted: #94a3b8; --border-cyan: rgba(0, 229, 255, 0.3);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-white); display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; gap: 18px; padding: 16px; }
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
        .alert-success { background: rgba(74, 222, 128, 0.12); border: 1px solid rgba(74, 222, 128, 0.45); color: #86efac; padding: 14px 18px; border-radius: 12px; font-size: 0.9rem; }
        .orders-layout { display: grid; grid-template-columns: 1fr 1.25fr; gap: 18px; align-items: start; }
        .panel { background: var(--bg-panel); border: 1px solid var(--border-cyan); border-radius: 18px; padding: 20px; }
        .order-card { background: var(--bg-card); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 14px; padding: 16px; margin-bottom: 12px; transition: 0.2s; }
        .order-card.selected { border-color: var(--cyan); box-shadow: 0 0 0 1px rgba(0, 229, 255, 0.25); }
        .order-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .status-pill { background: rgba(0, 229, 255, 0.15); color: var(--cyan); border: 1px solid var(--border-cyan); padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .btn-track { display: inline-flex; align-items: center; gap: 6px; background: var(--cyan); color: #030b17; text-decoration: none; font-weight: 700; font-size: 0.8rem; padding: 8px 12px; border-radius: 8px; margin-top: 10px; border: none; cursor: pointer; }
        .btn-track:disabled { opacity: 0.45; cursor: not-allowed; }
        .status-panel { background: var(--bg-card); border: 1px solid rgba(0, 229, 255, 0.22); border-radius: 14px; padding: 14px; margin-top: 14px; }
        .status-controls { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 10px; }
        .status-select { background: var(--bg-panel); color: #fff; border: 1px solid var(--border-cyan); border-radius: 8px; padding: 8px 10px; font-size: 0.82rem; outline: none; }
        .btn-secondary { background: rgba(0, 229, 255, 0.15); color: var(--cyan); border: 1px solid var(--border-cyan); border-radius: 8px; padding: 8px 12px; font-size: 0.8rem; font-weight: 700; cursor: pointer; }
        .timeline { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 18px 0 16px; }
        .step { background: var(--bg-card); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 10px; padding: 10px 8px; text-align: center; font-size: 0.78rem; color: var(--text-muted); }
        .step.done { border-color: var(--cyan); color: #fff; background: rgba(0, 229, 255, 0.12); }
        .step i { display: block; margin-bottom: 5px; color: var(--cyan); }
        .item-row { display: flex; align-items: center; gap: 12px; background: var(--bg-card); padding: 10px 12px; border-radius: 12px; margin-bottom: 10px; }
        .item-row img { width: 48px; height: 48px; object-fit: contain; border-radius: 8px; background: #071326; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.88rem; color: var(--text-muted); margin-top: 8px; }
        .logout-btn { color: #f87171; text-decoration: none; }
        @media (max-width: 980px) {
            body { grid-template-columns: 1fr; }
            .sidebar { height: auto; position: static; }
            .orders-layout { grid-template-columns: 1fr; }
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
                <a href="dashboard.php" class="nav-item"><div><i class="fa-solid fa-house"></i> Início</div></a>
                <a href="dashboard.php#carrinho" class="nav-item"><div><i class="fa-solid fa-cart-shopping"></i> Carrinho</div><span class="badge-cyan"><?= $totalItensCarrinho ?></span></a>
                <a href="pedidos.php" class="nav-item active"><div><i class="fa-solid fa-box"></i> Meus Pedidos</div></a>
            </nav>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(148,163,184,0.15); padding-top:14px;">
            <div><strong><?= htmlspecialchars($nomeUsuario) ?></strong></div>
            <a href="logout.php" class="logout-btn" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>Meus Pedidos</h2>
                <p style="color:var(--text-muted); font-size:0.9rem;">Acompanhe o status, avance etapas e veja os itens de cada compra.</p>
            </div>
            <a href="dashboard.php" class="btn-track" style="margin-top:0;"><i class="fa-solid fa-arrow-left"></i> Continuar comprando</a>
        </div>

        <?php if (isset($_GET['novo'])): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i> Pedido <strong>#<?= (int)$_GET['novo'] ?></strong> registrado com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status_atualizado'])): ?>
            <div class="alert-success">
                <i class="fa-solid fa-arrows-rotate"></i> Status do pedido atualizado no banco de dados!
            </div>
        <?php endif; ?>

        <?php if (empty($pedidos)): ?>
            <div class="panel" style="text-align:center; padding:40px;">
                <p style="color:var(--text-muted); margin-bottom:14px;">Você ainda não realizou nenhum pedido.</p>
                <a href="dashboard.php" class="btn-track">Explorar produtos</a>
            </div>
        <?php else: ?>
            <div class="orders-layout">
                <section class="panel">
                    <h3 style="margin-bottom:14px; font-size:1rem;">Histórico de compras</h3>
                    <?php foreach ($pedidos as $pedido):
                        $isSelected = $pedidoDetalhe && ((int)$pedido['id'] === (int)$pedidoDetalhe['id']);
                    ?>
                        <div class="order-card <?= $isSelected ? 'selected' : '' ?>">
                            <div class="order-top">
                                <strong>Pedido #<?= (int)$pedido['id'] ?></strong>
                                <span class="status-pill"><?= htmlspecialchars($pedido['status']) ?></span>
                            </div>
                            <div style="font-size:0.82rem; color:var(--text-muted);">
                                <?= date('d/m/Y \à\s H:i', strtotime($pedido['criado_em'])) ?>
                            </div>
                            <div style="margin-top:8px; font-weight:700; color:var(--cyan);">
                                Total: R$ <?= number_format((float)$pedido['total'], 2, ',', '.') ?>
                            </div>
                            <a href="pedidos.php?acompanhar=<?= (int)$pedido['id'] ?>" class="btn-track">
                                <i class="fa-solid fa-truck-fast"></i> Acompanhar compra
                            </a>
                        </div>
                    <?php endforeach; ?>
                </section>

                <section class="panel">
                    <?php if ($pedidoDetalhe): ?>
                        <div>
                            <h3>Acompanhamento — Pedido #<?= (int)$pedidoDetalhe['id'] ?></h3>
                            <small style="color:var(--text-muted);">Status atual: <strong style="color:var(--cyan);"><?= htmlspecialchars($pedidoDetalhe['status']) ?></strong></small>
                        </div>

                        <?php
                        $iconesEtapas = ['fa-check-circle', 'fa-boxes-stacked', 'fa-truck', 'fa-house-circle-check'];
                        ?>
                        <div class="timeline">
                            <?php foreach ($etapas as $idx => $etapa):
                                $concluida = ($idx <= $indiceAtual);
                            ?>
                                <div class="step <?= $concluida ? 'done' : '' ?>">
                                    <i class="fa-solid <?= $iconesEtapas[$idx] ?>"></i>
                                    <span><?= htmlspecialchars($etapa) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Painel para avançar ou definir status do pedido -->
                        <div class="status-panel">
                            <div style="font-size:0.85rem; font-weight:700; color:#fff;">
                                <i class="fa-solid fa-sliders" style="color:var(--cyan);"></i> Painel de Status do Pedido
                            </div>
                            <div class="status-controls">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="pedido_id" value="<?= (int)$pedidoDetalhe['id'] ?>">
                                    <input type="hidden" name="acao_status" value="avancar">
                                    <button type="submit" class="btn-track" style="margin-top:0;" <?= $jaEntregue ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-forward-step"></i>
                                        <?= $jaEntregue ? 'Pedido Entregue' : 'Avançar Status' ?>
                                    </button>
                                </form>

                                <form method="POST" style="display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="pedido_id" value="<?= (int)$pedidoDetalhe['id'] ?>">
                                    <input type="hidden" name="acao_status" value="definir">
                                    <select name="novo_status" class="status-select">
                                        <?php foreach ($etapas as $etapaOpcao): ?>
                                            <option value="<?= htmlspecialchars($etapaOpcao) ?>" <?= ($pedidoDetalhe['status'] === $etapaOpcao) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($etapaOpcao) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-secondary">Salvar status</button>
                                </form>
                            </div>
                        </div>

                        <h4 style="margin:18px 0 10px; font-size:0.95rem;">Itens do pedido</h4>
                        <?php foreach ($itensPedido as $item): ?>
                            <div class="item-row">
                                <img src="<?= htmlspecialchars($item['imagem_url'] ?? '') ?>" alt="<?= htmlspecialchars($item['nome_produto']) ?>">
                                <div style="flex:1;">
                                    <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($item['nome_produto']) ?></div>
                                    <small style="color:var(--text-muted);">Qtd: <?= (int)$item['quantidade'] ?> × R$ <?= number_format((float)$item['preco_unitario'], 2, ',', '.') ?></small>
                                </div>
                                <strong style="color:var(--cyan); font-size:0.9rem;">
                                    R$ <?= number_format((float)$item['preco_unitario'] * (int)$item['quantidade'], 2, ',', '.') ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>

                        <div style="border-top:1px solid rgba(148,163,184,0.15); padding-top:10px; margin-top:14px;">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>R$ <?= number_format((float)$pedidoDetalhe['subtotal'], 2, ',', '.') ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Frete</span>
                                <span><?= (float)$pedidoDetalhe['frete'] == 0 ? 'Grátis' : 'R$ ' . number_format((float)$pedidoDetalhe['frete'], 2, ',', '.') ?></span>
                            </div>
                            <div class="summary-row" style="color:#fff; font-weight:800; font-size:1rem; margin-top:10px;">
                                <span>Total pago</span>
                                <span style="color:var(--cyan);">R$ <?= number_format((float)$pedidoDetalhe['total'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>