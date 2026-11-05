# Cardápio Digital

Aplicação PHP 8.1+ e MySQL/MariaDB para exibir um cardápio público, receber escolhas de usuários e administrar produtos, categorias e pedidos.

## Requisitos

- PHP 8.1 ou superior com `pdo_mysql` e `mbstring`.
- MySQL 8+ ou MariaDB 10.5+.
- Um banco local isolado para desenvolvimento. Não use credenciais de produção.

## Instalação local

1. Crie um banco vazio, por exemplo `cardapio_dev`, e um usuário dedicado com permissão apenas nesse banco.
2. Configure as variáveis de ambiente usadas por `db.php`:

PowerShell:

```powershell
$env:DB_HOST = '127.0.0.1'
$env:DB_NAME = 'cardapio_dev'
$env:DB_USER = 'cardapio_dev'
$env:DB_PASS = 'uma-senha-local-forte'
```

Bash:

```bash
export DB_HOST=127.0.0.1 DB_NAME=cardapio_dev DB_USER=cardapio_dev
export DB_PASS='uma-senha-local-forte'
```

3. Aplique `database/schema.sql` e, opcionalmente, `database/seed.sql`:

```bash
mysql -u cardapio_dev -p cardapio_dev < database/schema.sql
mysql -u cardapio_dev -p cardapio_dev < database/seed.sql
```

4. Crie o primeiro administrador pela CLI. Não existe senha padrão nem cadastro público de administradores:

```powershell
$env:ADMIN_PASSWORD = 'escolha-uma-senha-com-12-caracteres'
php bin/create_admin.php 'Administrador' admin@example.test
Remove-Item Env:ADMIN_PASSWORD
```

5. Inicie o servidor de desenvolvimento na raiz do checkout:

```bash
php -S 127.0.0.1:8000
```

Abra [http://127.0.0.1:8000/public/](http://127.0.0.1:8000/public/). Administração: `/admin/login.php`.

## Fluxo

- O cardápio público lista categorias e produtos disponíveis, descrição, preço e imagem opcional.
- Usuários criam conta com nome, e-mail, telefone e restrições alimentares, entram e escolhem entre Ágape simples, Ágape de iniciação e Jantar ritualístico.
- Antes de enviar, podem consultar os ingredientes de cada produto e registrar observações.
- O painel administrativo gerencia categorias, limites de seleção, produtos, disponibilidade e pedidos; mostra contato e restrições informadas e permite atualizar status ou excluir pedidos.
- A criação do primeiro administrador é feita localmente via CLI. A rota de cadastro público sempre cria uma conta de cliente.

## API e proteção

Os formulários usam `POST`, token CSRF e consultas PDO preparadas. Senhas usam `password_hash` / `password_verify`; o papel do usuário vem do banco. Sessões regeneram o ID após login e usam cookies `HttpOnly` e `SameSite=Lax`. O banco não usa usuário/senha padrão; erros de conexão não são enviados ao navegador.

Rotas de formulário: `api/cadastro.php`, `api/login.php` e `api/enviar_menu.php`. O seed é um arquivo SQL local, não uma rota pública.

## Testes e limitações

Valide a sintaxe PHP com:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Rode o smoke test local sem conexão ao banco:

```bash
php tests/security_smoke.php
```

Os testes de integração de schema, seed, autenticação e CRUD devem ser executados contra uma instância MySQL/MariaDB de teste dedicada. Neste checkpoint, não havia MySQL/MariaDB nem Docker disponíveis no ambiente; portanto, essas operações não foram executadas e precisam ser homologadas com banco isolado.

O campo de restrições alimentares é informativo nesta versão; ele não decide automaticamente a segurança de um prato. Confirme ingredientes diretamente com o estabelecimento. O endereço de imagem aceita URL HTTPS ou caminhos locais em `/uploads/`; upload de arquivos não está implementado.
