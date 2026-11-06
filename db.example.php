<?php
// Exemplo de configuração documentado. O aplicativo usa db.php e exige estas
// variáveis de ambiente; não coloque credenciais diretamente neste arquivo.
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $required) {
    if (getenv($required) === false || getenv($required) === '') {
        throw new RuntimeException("Variável de ambiente obrigatória ausente: $required");
    }
}
