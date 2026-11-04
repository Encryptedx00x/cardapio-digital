# Cardápio Digital

Protótipo de cardápio digital para restaurantes, em PHP + MySQL, com painel administrativo
simples. Projeto inicial, não evoluído além do MVP.

## Funcionalidades

- Cardápio público com itens por categoria.
- Painel administrativo (login) para gerenciar o cardápio.
- API em PHP (cadastro, login, envio de menu, seed de dados de exemplo).

## Configuração

As credenciais do banco vêm de variáveis de ambiente (veja `db.example.php`):

```bash
export DB_HOST=localhost DB_NAME=cardapio DB_USER=root DB_PASS=suasenha
```

Crie o banco, rode `api/seed.php` uma vez para popular categorias e itens de exemplo e acesse
`public/index.php`. O acesso de administrador usa e-mail terminando em `@admin.com` com senha
protegida por hash (`password_hash`).

## Stack

PHP, MySQL/MariaDB (PDO), HTML, CSS.

> Protótipo de estudo. Defina credenciais fortes e um usuário administrador próprio antes de usar.
