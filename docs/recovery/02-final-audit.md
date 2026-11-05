# Auditoria final — Cardápio Digital

Revisão do PR #1 contra `origin/master`, preservando o commit `32f466f`.

## Defeitos comprovados e corrigidos

| Defeito | Reprodução | Correção |
| --- | --- | --- |
| Primeiro admin não era criado | MariaDB estrito: erro 1364 em `dietary_restrictions` | CLI informa o campo obrigatório explicitamente |
| Login/cadastro perdiam sessão | `output_buffering=0`: sessão iniciada depois do HTML, sem cookie | Sessão começa antes da saída nos três formulários |
| Redirect quebrado no Windows | `Location` continha barra invertida e terminava em 404 | Normalização de separadores no caminho base |
| CSV falhava em SQL estrito | `ONLY_FULL_GROUP_BY`: erro 1055 | Consulta sem agrupamento ambíguo, itens obtidos separadamente |
| CSV trocava itens e observações | Ordem dos valores não correspondia ao cabeçalho | Mapeamento explícito; evita também truncamento de `GROUP_CONCAT` |
| Categoria duplicada gerava 500 | POST repetido causava PDOException não tratada | Conflito HTTP 409, sem detalhes SQL na resposta |
| Validações inválidas retornavam sucesso | Preço negativo encerrava a resposta com HTTP 200 | Rejeição HTTP 400 e validação de parâmetros escalares |
| Seleção mista descartava IDs inválidos | API filtrava o ID inválido e aceitava parte do pedido | Rejeição integral da seleção inválida |

Também: porta configurável via `DB_PORT`, scripts CLI rejeitam HTTP e token CSRF com tipo inválido é rejeitado sem warning.

## Evidências

- MariaDB 11.4.13 portátil, sem serviço instalado, exclusivamente `127.0.0.1:33317`.
- Pacote oficial: https://archive.mariadb.org/mariadb-11.4.13/winx64-packages/.
- SHA256 do ZIP conferido: `d62986d433eeebfde218560b276103831604a61e929e87f1a17f5aebd80257e2`.
- Banco `cardapio_test_sol_run2`, usuário dedicado com privilégios apenas no banco de testes.
- PHP 8.3.32; SQL mode com `STRICT_ALL_TABLES` e `ONLY_FULL_GROUP_BY`.
- `python tests/http_integration.py`: **71 verificações aprovadas**, sem warnings/fatals PHP.
- Cobertura: schema/seed idempotentes, criação/admin/login, CRUD de categorias/produtos, preço persistido, disponibilidade, pedidos e status, exclusão e snapshots históricos, permissões cliente/admin, CSRF sem escrita, SQL injection sem bypass/DDL, XSS escapado em HTML e CSV com colunas/fórmulas protegidas.
- `php tests/security_smoke.php`: 5 verificações aprovadas.
- Lint de todos os PHPs e `git diff --check`: aprovados.
- Nenhuma VPS, banco compartilhado, conta real ou deploy foi usado.

## Decisão

**GO para merge do MVP**, com a ressalva de que a homologação executada foi MariaDB, não MySQL 8. Os campos informativos de restrições alimentares, ausência de upload e ausência de migração automática de bancos legados continuam documentados. O PR permanece draft para decisão do proprietário.
