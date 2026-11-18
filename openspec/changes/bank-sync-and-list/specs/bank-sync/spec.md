## ADDED Requirements

### Requirement: Autenticação no Pluggy só no servidor
O sistema SHALL autenticar no Pluggy apenas no servidor, com credenciais lidas do `.env`, e SHALL NOT expor client id, client secret ou API key ao navegador.

#### Scenario: Props do Inertia não carregam credenciais
- **WHEN** qualquer página do app é renderizada
- **THEN** nenhuma prop enviada ao Vue contém o client id, o client secret ou a API key do Pluggy

### Requirement: Sync idempotente de transações
O sistema SHALL gravar cada transação do Pluggy uma única vez, identificada por `pluggy_transaction_id`.

#### Scenario: Rodar o sync duas vezes
- **WHEN** o sync roda duas vezes seguidas com a mesma resposta do Pluggy contendo 3 transações
- **THEN** existem exatamente 3 transações com `source = pluggy`

#### Scenario: Transação muda de pendente para efetivada
- **WHEN** uma transação já gravada com `status = pending` volta do Pluggy com status efetivado
- **THEN** a mesma linha passa a `status = posted`, sem criar outra

### Requirement: Conversão de valores para centavos
O sistema SHALL gravar valores em centavos inteiros e positivos, com a direção derivada do tipo da transação.

#### Scenario: Débito com centavos
- **WHEN** o Pluggy devolve `amount = -52.9` e `type = DEBIT`
- **THEN** a transação é gravada com `amount_cents = 5290` (R$ 52,90) e `direction = out`

#### Scenario: Crédito com arredondamento binário
- **WHEN** o Pluggy devolve `amount = 0.29` e `type = CREDIT`
- **THEN** a transação é gravada com `amount_cents = 29` (R$ 0,29) e `direction = in`

### Requirement: Sync respeita o liga/desliga por conexão
O sistema SHALL sincronizar apenas conexões com `sync_enabled = true`.

#### Scenario: Conexão desligada
- **WHEN** o sync agendado roda e o Banco 2 está com `sync_enabled = false`
- **THEN** nenhuma chamada ao Pluggy é feita para o item do Banco 2, e as transações dele permanecem como estavam

### Requirement: Janela de busca
O sistema SHALL buscar os últimos 90 dias no primeiro sync de uma conexão e, nos seguintes, de `last_synced_at` menos 7 dias até hoje.

#### Scenario: Sync seguinte
- **WHEN** uma conexão com `last_synced_at = 2026-11-15` sincroniza em 2026-11-16
- **THEN** a busca de transações usa a data inicial 2026-11-08

### Requirement: Falha não apaga dados
O sistema SHALL registrar o erro na conexão e manter todas as transações já gravadas quando o Pluggy falhar.

#### Scenario: Pluggy responde 401
- **WHEN** a autenticação no Pluggy responde 401 após as três tentativas
- **THEN** a conexão fica com `status = error`, a mensagem é gravada e nenhuma transação é apagada
