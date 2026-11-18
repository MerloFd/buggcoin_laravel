## ADDED Requirements

### Requirement: Lista de transações do mês
O sistema SHALL mostrar as transações do mês atual agrupadas por dia, da mais recente para a mais antiga, com descrição, categoria, banco, tipo de conta e valor com sinal.

#### Scenario: Gasto no cartão
- **WHEN** existe uma transação `out` de 5290 centavos, "iFood", na conta de cartão do Banco 1, em 2026-11-15
- **THEN** ela aparece no grupo "HOJE · 15 NOV" como "iFood", "Banco 1 · cartão" e "−R$ 52,90"

#### Scenario: Mês sem transações
- **WHEN** o mês atual não tem nenhuma transação
- **THEN** a tela mostra um estado vazio com o botão "Sincronizar agora"

### Requirement: Origem sempre visível
O sistema SHALL indicar em cada transação se ela veio do banco ou foi lançada à mão, sem depender só de cor.

#### Scenario: Transação do banco
- **WHEN** uma transação tem `source = pluggy`
- **THEN** o item mostra o nome do banco em texto, além do selo visual

### Requirement: Filtro por origem
O sistema SHALL filtrar a lista entre Todas, Do banco e Manuais, mantendo o filtro na URL.

#### Scenario: Filtrar só o banco
- **WHEN** o usuário toca em "Do banco"
- **THEN** a lista mostra só transações com `source = pluggy` e a URL passa a ter `?source=pluggy`

### Requirement: Sincronizar agora
O sistema SHALL permitir disparar o sync manualmente pela tela Bancos e mostrar quando cada conexão sincronizou pela última vez.

#### Scenario: Botão Sincronizar agora
- **WHEN** o usuário toca em "Sincronizar agora"
- **THEN** um job de sync é enfileirado para cada conexão ligada e o botão fica desabilitado até os jobs terminarem

### Requirement: Tela Bancos
O sistema SHALL listar cada conexão com nome do banco, contas, status, última sincronização e o liga/desliga do sync.

#### Scenario: Desligar o sync de um banco
- **WHEN** o usuário desliga o sync do Banco 2
- **THEN** `sync_enabled` passa a `false` e a tela continua mostrando as transações já gravadas desse banco
