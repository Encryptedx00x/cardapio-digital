# Checkpoint 01 — Cardápio Digital

## Estado

**FUNCIONAL COM RESSALVAS.** O MVP foi implementado em PHP/PDO com schema e seed versionados. A homologação CRUD com MySQL/MariaDB ainda está pendente: o ambiente local tem PHP 8.3 e `pdo_mysql`, mas não possui servidor MySQL/MariaDB ou Docker disponível.

## Entregas

- Menu público responsivo com categorias, itens disponíveis, descrição, preço e imagem opcional.
- Cadastro e login de usuários com telefone e campo informativo de restrições alimentares.
- Seleção do tipo de menu, itens, consulta de ingredientes, observações e envio de pedido persistido.
- Painel administrativo com CRUD de categorias/produtos, limites por categoria, disponibilidade, status e exclusão de pedidos, lista de usuários e exportação CSV.
- Papéis separados para cliente e administrador. Não há cadastro público de admin; primeiro admin é criado pelo CLI com senha fornecida pelo operador.
- Schema/seed SQL, prepared statements, CSRF em formulários, escaping de HTML, regeneração de sessão após login e configuração sem credenciais padrão.
- Removido o endpoint HTTP de seed; a carga de demonstração agora é um SQL local.

## Validação executada

- `php -l` em todos os arquivos PHP: aprovado.
- `php tests/security_smoke.php`: 5 verificações passaram (token CSRF, escape HTML e autorização cliente/admin).
- `git diff --check`: aprovado.
- Nenhum banco de produção, VPS ou serviço externo foi acessado.

## Pendências de homologação

- Aplicar `database/schema.sql` e `database/seed.sql` em MySQL/MariaDB isolado.
- Validar cadastro, login, pedidos, CRUD, limites de seleção, status e exportação com dados sintéticos no banco.
- O campo de restrições não filtra pratos automaticamente; deve ser tratado como informação para conferência humana.
- Upload de imagem e edição de perfil de usuário não estão nesta entrega; imagens aceitam URL HTTPS ou caminho local.
