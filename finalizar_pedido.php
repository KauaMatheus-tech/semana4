<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['carrinho'])) {
    header("Location: dashboard.php");
    exit();
}

$usuarioId = (int)$_SESSION['usuario_id'];
$carrinho = $_SESSION['carrinho'];

try {
    $pdo->beginTransaction();

    $subtotal = 0;
    $itensValidados = [];

    // Busca o preço atual de cada produto no banco para garantir integridade
    $stmtProd = $pdo->prepare("SELECT id, nome, preco, imagem_url FROM produtos WHERE id = ?");

    foreach ($carrinho as $id => $item) {
        $prodId = (int)($item['id'] ?? $id);
        $qtd = (int)($item['qtd'] ?? 0);

        if ($qtd <= 0) {
            continue;
        }

        $stmtProd->execute([$prodId]);
        $prodDb = $stmtProd->fetch();

        if ($prodDb) {
            $precoUnit = (float)$prodDb['preco'];
            $subtotal += ($precoUnit * $qtd);
            $itensValidados[] = [
                'produto_id' => (int)$prodDb['id'],
                'nome_produto' => $prodDb['nome'],
                'preco_unitario' => $precoUnit,
                'quantidade' => $qtd,
                'imagem_url' => $prodDb['imagem_url']
            ];
        }
    }

    if (empty($itensValidados)) {
        $pdo->rollBack();
        header("Location: dashboard.php");
        exit();
    }

    // Mesma regra de frete do dashboard
    $frete = ($subtotal >= 199 || $subtotal == 0) ? 0.00 : 29.90;
    $total = $subtotal + $frete;

    $stmtPedido = $pdo->prepare("
        INSERT INTO pedidos (usuario_id, subtotal, frete, total, status)
        VALUES (:usuario_id, :subtotal, :frete, :total, 'Confirmado')
    ");
    $stmtPedido->execute([
        'usuario_id' => $usuarioId,
        'subtotal' => $subtotal,
        'frete' => $frete,
        'total' => $total
    ]);

    $pedidoId = (int)$pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, preco_unitario, quantidade, imagem_url)
        VALUES (:pedido_id, :produto_id, :nome_produto, :preco_unitario, :quantidade, :imagem_url)
    ");

    foreach ($itensValidados as $item) {
        $stmtItem->execute([
            'pedido_id' => $pedidoId,
            'produto_id' => $item['produto_id'],
            'nome_produto' => $item['nome_produto'],
            'preco_unitario' => $item['preco_unitario'],
            'quantidade' => $item['quantidade'],
            'imagem_url' => $item['imagem_url']
        ]);
    }

    $pdo->commit();
    unset($_SESSION['carrinho']);

    header("Location: pedidos.php?novo=" . $pedidoId);
    exit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao salvar pedido: " . $e->getMessage());
    $_SESSION['erro_pedido'] = "Não foi possível concluir o pedido. Tente novamente.";
    header("Location: dashboard.php");
    exit();
}