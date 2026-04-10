-- ============================================
-- MUSICSTORE - Banco de Dados MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS musicstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE musicstore;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de produtos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category_id INT,
    image_url VARCHAR(500),
    brand VARCHAR(100),
    sku VARCHAR(50) UNIQUE,
    featured BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabela de carrinho
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- DADOS INICIAIS
-- ============================================

-- Admin padrão (senha: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Administrador', 'admin@musicstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categorias
INSERT INTO categories (name, slug, description) VALUES
('Guitarras', 'guitarras', 'Guitarras elétricas e acústicas'),
('Baixos', 'baixos', 'Baixos elétricos e acústicos'),
('Baterias', 'baterias', 'Baterias acústicas e eletrônicas'),
('Teclados', 'teclados', 'Teclados e sintetizadores'),
('Acessórios', 'acessorios', 'Palhetas, cordas, cabos e mais'),
('Amplificadores', 'amplificadores', 'Amplificadores e caixas de som');

-- Produtos de exemplo
INSERT INTO products (name, description, price, stock, category_id, image_url, brand, sku, featured) VALUES
('Guitarra Fender Stratocaster', 'Guitarra elétrica clássica com corpo em alder e braço em maple. 3 captadores single-coil.', 4999.90, 5, 1, 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=500', 'Fender', 'GTR-STRAT-001', TRUE),
('Gibson Les Paul Standard', 'Guitarra elétrica premium com corpo em mogno e tampo em maple flamed. 2 humbuckers.', 8999.90, 3, 1, 'https://images.unsplash.com/photo-1525201548942-d8732f6617a0?w=500', 'Gibson', 'GTR-LP-001', TRUE),
('Baixo Fender Jazz Bass', 'Baixo elétrico versátil com 2 captadores single-coil. Ideal para todos os estilos.', 3499.90, 8, 2, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500', 'Fender', 'BXO-JB-001', FALSE),
('Bateria Pearl Export', 'Kit de bateria acústica completo com 5 tambores, pratos e hardware.', 5999.90, 2, 3, 'https://images.unsplash.com/photo-1519892300165-cb5542fb47c7?w=500', 'Pearl', 'BAT-EXP-001', TRUE),
('Teclado Roland Juno DS88', 'Teclado sintetizador com 88 teclas pesadas, sons de alta qualidade.', 6799.90, 4, 4, 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=500', 'Roland', 'TEC-JUNO-001', FALSE),
('Amplificador Marshall DSL40CR', 'Amplificador valvulado 40W com 2 canais. Ideal para rock e blues.', 3999.90, 6, 6, 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=500', 'Marshall', 'AMP-DSL-001', TRUE),
('Kit de Cordas Ernie Ball', 'Kit com 6 jogos de cordas 010-046 para guitarra. Pure Nickel.', 149.90, 50, 5, 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=500', 'Ernie Ball', 'ACC-CRD-001', FALSE),
('Guitarra Yamaha Pacifica 112V', 'Guitarra elétrica versátil para iniciantes e intermediários. Ótimo custo-benefício.', 1999.90, 10, 1, 'https://images.unsplash.com/photo-1507838153414-b4b713384a76?w=500', 'Yamaha', 'GTR-PAC-001', FALSE),
('Pedal Boss DS-1 Distortion', 'Pedal de distorção clássico. Usado por guitarristas do mundo todo há décadas.', 399.90, 15, 5, 'https://images.unsplash.com/photo-1558098329-a11cff621064?w=500', 'Boss', 'ACC-DS1-001', FALSE),
('Teclado Casio CT-S300', 'Teclado 61 teclas ideal para iniciantes. Leve e portátil com 48 ritmos.', 699.90, 12, 4, 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=500', 'Casio', 'TEC-CTS-001', FALSE);
