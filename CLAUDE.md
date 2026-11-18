# BuggCoin — regras de trabalho

App pessoal de finanças: Laravel 13 + Inertia 3 + Vue 3.5 (TypeScript), MySQL 8.4, PWA.
Responde três perguntas: quanto gastei no mês, quanto ainda posso gastar, quanto devo nos próximos meses.

## Fontes da verdade (ler antes de propor qualquer coisa)

- Modelo de dados, regras de negócio e registro de decisões: https://claude.ai/artifact/K62HwMTMFj63AdopaT4bbX
- Design system (tokens, tipografia, regras de tema): https://claude.ai/artifact/4Y41T7AUPNH1hbWvkxQAD9
- Telas mobile de referência: https://claude.ai/artifact/PYdJpovaNa1gCkPyCCRFiF
- Mudanças em andamento: `openspec/changes/`

Se o código ou um pedido contradiz essas fontes: pare e pergunte. Não resolva sozinho.

## Como trabalhar comigo

- Responda em pt-BR. Código, nomes e comentários em inglês. Texto de interface em pt-BR.
- Uma mudança do OpenSpec por vez. Nada fora do escopo da mudança aberta, nem "aproveitando".
- Antes de cada tarefa, diga em 2 linhas o que vai fazer. Depois, diga o que mudou e quais arquivos.
- Diffs pequenos. Se uma tarefa passar de ~300 linhas, quebre e me avise.
- Regras de negócio (conciliação, saldo livre, parcelas): eu escrevo os casos de teste; você implementa até passarem. Não altere um teste meu para ele passar: me pergunte.

## Regras invioláveis

- Dinheiro sempre em centavos inteiros (`BIGINT`), valor sempre positivo, direção em coluna própria (`in` / `out`). Nunca `float` para dinheiro. Única exceção: `accounts.balance_cents` tem sinal, porque saldo é posição, não movimentação (cheque especial, fatura devida).
- Nenhuma lib nova sem estar no registro de decisões. Precisa de uma? Proponha e espere.
- `PLUGGY_CLIENT_ID` e `PLUGGY_CLIENT_SECRET` só no servidor, só no `.env`. Nunca em props do Inertia, logs ou testes.
- Nunca assuma o formato de um endpoint do Pluggy. Leia a documentação atual (https://docs.pluggy.ai) antes de implementar e cite o endpoint usado no código.
- Sync é idempotente: rodar duas vezes nunca duplica. Índice único em `pluggy_transaction_id`.
- Conciliação nunca é automática: o app sugere, o usuário confirma.
- Estrutura padrão do Laravel. Sem `app/Modules`. O client do Pluggy fica em `app/Services/Pluggy/`.
- Fila e agendador com driver `database`. Sem Redis.

## Interface

- Tokens do design system via CSS variables no tema do shadcn-vue. Sem hex solto em componente.
- Tema escuro é o padrão. `--primary-foreground` é escuro no tema escuro.
- Raio máximo de 6px. Elevação = superfície mais clara + borda de 1px, nunca sombra.
- Valores monetários com `tabular-nums`. Entrada/saída também por sinal (+/−), nunca só por cor.
- Alvo de toque mínimo de 44×44px.
- Animação: só o que está na seção Movimento do doc; 150–250ms, só `transform` e `opacity`, respeitando `prefers-reduced-motion`.

## Testes e qualidade

- Pest para regras e features. Teste de navegador do Pest para um caminho feliz por tela.
- Antes de dizer que terminou: `composer test` (Pint, PHPStan nível 7 e Pest), `npm run types:check` (vue-tsc) e `npm run check` (lint e formatação do front) passando.
- Chamadas ao Pluggy nos testes sempre com `Http::fake()`. Nunca bater na API real em teste.
