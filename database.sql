CREATE DATABASE IF NOT EXISTS techstore
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE techstore;

-- 1. Tabela de Usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    cidade_uf VARCHAR(100) DEFAULT NULL,
    cep VARCHAR(15) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabela de Categorias
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    icone VARCHAR(50) NOT NULL
);

-- 3. Tabela de Produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    cor VARCHAR(40) DEFAULT 'Preto',
    preco DECIMAL(10, 2) NOT NULL,
    estoque INT NOT NULL DEFAULT 10,
    avaliacoes INT DEFAULT 0,
    imagem_url VARCHAR(255) NOT NULL,
    destaque BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- 4. Tabela de Favoritos (Coração nos cards)
CREATE TABLE favoritos (
    usuario_id INT NOT NULL,
    produto_id INT NOT NULL,
    PRIMARY KEY (usuario_id, produto_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- 5. Tabela de Pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_pedido VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    frete DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('Pedido realizado', 'Em preparação', 'Em trânsito', 'Entregue', 'Cancelado') DEFAULT 'Pedido realizado',
    endereco_entrega VARCHAR(255) NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 6. Tabela de Itens do Pedido
CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
);

-- 7. Tabela de Histórico de Rastreamento
CREATE TABLE rastreamento_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    descricao VARCHAR(150) NOT NULL,
    data_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
);

-- ==========================================================
-- INSERINDO DADOS INICIAIS (Baseados nas telas do sistema)
-- ==========================================================

-- Usuário padrão (Senha: 123456)
INSERT INTO usuarios (nome, email, senha, endereco, cidade_uf, cep) VALUES
('Kauã Matheus', 'kaua@techstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rua das Flores, 123 - Centro', 'Montes Claros/MG', '39400-000');

-- Categorias
INSERT INTO categorias (id, nome, icone) VALUES
(1, 'Smartphones', 'fa-mobile-screen-button'),
(2, 'Notebooks', 'fa-laptop'),
(3, 'Fones', 'fa-headphones'),
(4, 'Smart TV', 'fa-tv'),
(5, 'Games', 'fa-gamepad'),
(6, 'Acessórios', 'fa-clock');

-- Produtos
INSERT INTO produtos (categoria_id, nome, cor, preco, estoque, avaliacoes, imagem_url) VALUES
(1, 'iPhone 15 128GB', 'Preto', 4999.00, 15, 124, 'https://placehold.co/160x140/07152b/00e5ff?text=iPhone+15'),
(2, 'Notebook Acer Aspire 5', 'Prata', 2799.00, 8, 98, 'https://placehold.co/160x140/07152b/00e5ff?text=Acer+Aspire+5'),
(3, 'Fone JBL Tune 520BT', 'Preto', 349.00, 30, 76, 'https://placehold.co/160x140/07152b/00e5ff?text=JBL+Tune+520BT'),
(4, 'Smart TV 55" 4K', 'Preto', 2499.00, 10, 63, 'https://placehold.co/160x140/07152b/00e5ff?text=Smart+TV+55+4K'),
(5, 'Console PlayStation 5', 'Branco', 3999.00, 5, 210, 'https://placehold.co/160x140/07152b/00e5ff?text=PlayStation+5'),
(6, 'Smartwatch Amazfit GTS 4', 'Preto', 1099.00, 20, 87, 'https://placehold.co/160x140/07152b/00e5ff?text=Amazfit+GTS+4'),
(6, 'Câmera GoPro HERO12', 'Preto', 2299.00, 12, 54, 'https://placehold.co/160x140/07152b/00e5ff?text=GoPro+HERO12'),
(6, 'Mouse Gamer Logitech G502', 'Preto', 299.00, 25, 91, 'https://placehold.co/160x140/07152b/00e5ff?text=Logitech+G502');

-- Pedido de exemplo (#54872)[cite: 2]
INSERT INTO pedidos (id, codigo_pedido, usuario_id, subtotal, frete, total, status, endereco_entrega, data_pedido) VALUES
(1, '#54872', 1, 5348.00, 0.00, 5348.00, 'Em trânsito', 'Rua das Flores, 123 - Centro, Montes Claros/MG - CEP 39400-000', '2025-10-26 14:32:00');

INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES
(1, 1, 1, 4999.00),
(1, 3, 1, 349.00);

INSERT INTO rastreamento_logs (pedido_id, descricao, data_evento) VALUES
(1, 'Pedido em separação', '2025-10-14 12:05:00'),
(1, 'Coletado pela transportadora', '2025-10-14 16:32:00'),
(1, 'Em transporte', '2025-10-15 08:17:00'),
(1, 'Saiu para entrega', '2025-10-15 10:24:00');