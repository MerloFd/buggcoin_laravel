# Design: bank-sync-and-list

## Fluxo

1. Gatilho: agendador (`everyFourHours`) ou o botão "Sincronizar agora".
2. Um `SyncBankConnection` por conexão com `sync_enabled = true`, na fila `database`.
3. O job autentica no Pluggy (`POST /auth` com client id e secret do `.env`) e guarda a API key em cache até perto de expirar.
4. Busca as contas do item e atualiza `accounts` (saldo e limite).
5. Busca as transações de cada conta numa janela de datas e faz upsert por `pluggy_transaction_id`.
6. Atualiza `last_synced_at` e `status` da conexão.

## Decisões

**Janela de busca.** Primeiro sync: últimos 90 dias. Depois: de `last_synced_at` menos 7 dias até hoje. A margem de 7 dias pega transações que o banco publica com atraso ou que mudam de pendente para efetivada.

**Idempotência.** Índice único em `transactions.pluggy_transaction_id`. Upsert atualiza `status`, `amount_cents`, `description`, `occurred_on` e `pluggy_category`, mas nunca toca em campos que o usuário editou (categoria própria, `merged_into_id`, `is_transfer`, que chegam em mudanças futuras).

**Colunas só do necessário.** A migration de `transactions` nesta mudança cria só o que o sync e a lista usam. `category_id`, `is_transfer`, `merged_into_id`, `bill_id` e as colunas de parcela entram nas mudanças que as usam, cada uma com a sua migration.

**Tipos de conta.** Só conta corrente (`checking`) e cartão (`credit_card`) entram no sync. Poupança e investimento que o Pluggy devolver são ignorados nesta mudança. `manual` fica para `manual-entries`.

**Saldo com sinal.** `accounts.balance_cents` é `BIGINT` com sinal: saldo é posição, não movimentação, e pode ficar negativo. `credit_limit_cents` e todos os valores de transação continuam positivos.

**Fuso.** O app roda em `America/Sao_Paulo` (`APP_TIMEZONE`), para que "mês atual" e "hoje" sigam o horário de Brasília.

**Categoria do Pluggy.** Coluna `pluggy_category` (`VARCHAR`, nula) guarda o texto da categoria que o Pluggy manda. A lista mostra esse texto. Quando as categorias próprias chegarem, ele vira só sugestão.

**Conversão de valor.** Pluggy devolve `amount` decimal e `type` (`DEBIT` / `CREDIT`). Gravamos `amount_cents = (int) round(abs($amount) * 100)` e `direction = out` para DEBIT, `in` para CREDIT. Verificar no payload real se transações de cartão seguem a mesma convenção de sinal antes de fechar a regra.

**Registro de conexões.** Nesta mudança, o item ID de cada banco do Meu Pluggy é cadastrado de duas formas, com a mesma lógica (uma action `ConnectBank`): o comando `bank:connect {itemId}` e o botão "Adicionar banco" da tela Bancos, que abre um campo para colar o item ID. A action busca o item no Pluggy (nome e logo do conector) e cria a `BankConnection`. Item ID inexistente ou já cadastrado: nada é criado e a tela mostra o erro. O widget Pluggy Connect fica para depois.

**Falhas.** Erro de autenticação ou item com erro: o job grava `status = error` e a mensagem, sem apagar nada. A tela Bancos mostra o status. Três tentativas com backoff antes de falhar.

**Paginação.** Transações vêm de `GET /v2/transactions` (cursor: anexar `next` ao caminho até vir nulo), porque `GET /transactions` está depreciado e sai do ar em 2026-12-31. Contas vêm de `GET /accounts` (`page` / `totalPages`). O client percorre todas as páginas.

## Achados da documentação (lida em 2026-11-25)

Fontes: https://api.pluggy.ai/oas3.json e https://docs.pluggy.ai (Authentication, Transaction, Credit card statement cycle, Account, Personal use).

- `POST /auth` devolve `apiKey`, válida por 2 horas, enviada no header `X-API-KEY`.
- `type` já vem normalizado para cartão: compra é `DEBIT`, pagamento é `CREDIT`. A regra de conversão (valor absoluto + `type`) vale para conta e cartão.
- **O id pode mudar.** Se descrição, valor ou data mudarem e não houver id da instituição, o Pluggy apaga a transação e cria outra com id novo, comum no fechamento da fatura. `providerId` é o identificador estável, mas só em conectores Open Finance.
- **Transação nova com data antiga.** A doc manda buscar novidades por `createdAtFrom`, nunca por `dateFrom`. Os dois não podem ser combinados.
- **Datas em UTC.** A doc manda converter para GMT-3, mas os exemplos vêm à meia-noite UTC, que convertida cairia no dia anterior. Conferir no payload real.
- **Moeda.** Compra internacional traz `currencyCode` estrangeiro; o valor na moeda da conta fica em `amountInAccountCurrency`.
- **Pendentes futuras.** `PENDING` inclui parcelas futuras e pagamentos agendados, com data à frente.
- `category` exige plano Pro, então pode vir nula no Meu Pluggy.
- Meu Pluggy: grátis, até 5 conexões, atualiza a cada 24 horas, autorização vale 1 ano (`consentExpiresAt`). Autorização vencida devolve listas vazias, sem erro.

## Perguntas abertas

- Uma transação pendente mantém o mesmo id quando vira efetivada? Se não mantiver, a regra de upsert precisa de outra chave. Verificar com dados reais na primeira semana.
- Quanto tempo o Open Finance leva para mostrar uma compra nos dois bancos? Medir e anotar no doc.
- O conector de um item do Meu Pluggy traz o nome do banco ou só "MeuPluggy"? Se for o segundo, `bank:connect` precisa de um nome próprio. Conferir em 6.1.
