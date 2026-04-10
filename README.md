# 🎸 MusicStore — E-commerce de Instrumentos Musicais

> Projeto acadêmico de e-commerce completo com Vue.js (frontend) e PHP (backend), banco de dados MySQL.

---

## 📁 Estrutura do Projeto

```
musicstore/
├── backend/
│   ├── .htaccess
│   ├── config/
│   │   ├── database.php       ← Configuração de conexão MySQL
│   │   └── database.sql       ← Script de criação do banco + dados iniciais
│   ├── middleware/
│   │   └── auth.php           ← JWT, CORS, funções auxiliares
│   └── api/
│       ├── auth.php           ← Login, registro, perfil
│       ├── products.php       ← CRUD de produtos
│       ├── cart.php           ← Carrinho de compras
│       ├── orders.php         ← Pedidos
│       ├── categories.php     ← Categorias
│       └── admin_stats.php    ← Dashboard admin
└── frontend/
    └── index.html             ← App Vue.js completo (single-file)
```

---

## ⚙️ Como Configurar

### 1. Pré-requisitos
- **XAMPP / WAMP / Laragon** (ou Apache + PHP 8.1+ + MySQL)
- Navegador moderno

### 2. Banco de Dados
1. Inicie o MySQL no XAMPP (ou seu servidor local)
2. Abra o **phpMyAdmin** em `http://localhost/phpmyadmin`
3. Clique em **Importar** e selecione `backend/config/database.sql`
4. Ou cole o conteúdo no campo SQL e execute

### 3. Colocar o projeto no servidor

**Com XAMPP:**
```
Copie a pasta `musicstore` inteira para:
C:\xampp\htdocs\musicstore
```

**Com Laragon:**
```
Copie para:
C:\laragon\www\musicstore
```

### 4. Configurar a conexão do banco
Edite o arquivo `backend/config/database.php`:
```php
define('DB_HOST', 'localhost');  // Host do MySQL
define('DB_USER', 'root');       // Usuário do MySQL
define('DB_PASS', '');           // Senha (vazia no XAMPP padrão)
define('DB_NAME', 'musicstore'); // Nome do banco
```

### 5. Configurar a URL da API no frontend
Abra `frontend/index.html` e encontre a linha:
```javascript
const API = 'http://localhost/musicstore/backend/api';
```
Ajuste conforme o endereço do seu servidor se necessário.

### 6. Acessar o sistema
- **Frontend (Loja):** `http://localhost/musicstore/frontend/index.html`
- **API Backend:** `http://localhost/musicstore/backend/api/`

---

## 🔐 Credenciais Padrão

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | admin@musicstore.com | admin123 |

---

## 📋 Funcionalidades

### 👤 Cliente
- [x] Landing page com produtos em destaque
- [x] Catálogo completo com busca e filtro por categoria
- [x] Visualização detalhada de produto
- [x] Cadastro e login com JWT
- [x] Carrinho de compras (sidebar deslizante)
- [x] Controle de quantidade no carrinho
- [x] Finalizar pedido com endereço de entrega
- [x] Histórico de pedidos com status
- [x] Detalhes de cada pedido

### 🔧 Admin
- [x] Dashboard com métricas (receita, pedidos, clientes)
- [x] Lista de produtos mais vendidos
- [x] Pedidos recentes
- [x] Gerenciamento completo de produtos (criar, editar, excluir)
- [x] Upload de imagem via URL
- [x] Controle de estoque
- [x] Destaque de produtos
- [x] Ativar/desativar produtos
- [x] Gerenciamento de pedidos com troca de status
- [x] Visualização de detalhes dos pedidos

---

## 🛠️ Tecnologias

| Camada | Tecnologia |
|--------|------------|
| Frontend | Vue.js 3 (CDN, sem build tools) |
| Backend | PHP 8.1+ (puro, sem framework) |
| Banco | MySQL 8+ via PDO |
| Auth | JWT (implementação própria, sem biblioteca) |
| Estilo | CSS puro com variáveis (sem framework CSS) |
| Fonts | Google Fonts (Bebas Neue, DM Sans, Space Mono) |

---

## 🔌 Endpoints da API

### Auth
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/auth.php?action=login` | Login |
| POST | `/auth.php?action=register` | Cadastro |
| GET  | `/auth.php?action=me` | Perfil atual (requer token) |

### Produtos
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/products.php` | Listar produtos ativos |
| GET | `/products.php?id={id}` | Detalhe de produto |
| POST | `/products.php` | Criar produto (admin) |
| PUT | `/products.php?id={id}` | Editar produto (admin) |
| DELETE | `/products.php?id={id}` | Excluir produto (admin) |

### Carrinho
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/cart.php` | Ver carrinho |
| POST | `/cart.php?action=add` | Adicionar item |
| PUT | `/cart.php?action=update` | Atualizar quantidade |
| DELETE | `/cart.php?action=remove&product_id={id}` | Remover item |
| DELETE | `/cart.php?action=clear` | Limpar carrinho |

### Pedidos
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/orders.php` | Listar pedidos |
| GET | `/orders.php?id={id}` | Detalhe de pedido |
| POST | `/orders.php` | Criar pedido |
| PUT | `/orders.php?id={id}` | Atualizar status (admin) |

---

## ⚠️ Observações

- O sistema de **pagamento é simulado** (conforme requisito acadêmico)
- As senhas são armazenadas com `password_hash()` (bcrypt)
- O JWT é implementado sem biblioteca externa (puro PHP)
- Não é necessário Node.js, npm ou nenhuma ferramenta de build
- Todo o frontend é um único arquivo HTML autocontido

---

*Projeto desenvolvido para fins acadêmicos — Disciplina de Desenvolvimento Web*
