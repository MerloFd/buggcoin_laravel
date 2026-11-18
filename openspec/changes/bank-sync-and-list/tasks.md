# Tarefas: bank-sync-and-list

## 1. Fundação
- [ ] 1.1 `laravel new buggcoin` com o starter kit de Vue (Inertia, TypeScript, shadcn-vue, Fortify, Wayfinder) e Pest
- [ ] 1.2 `docker-compose.yml` só com MySQL 8.4; `.env.example` com `PLUGGY_CLIENT_ID`, `PLUGGY_CLIENT_SECRET`, `PLUGGY_BASE_URL`
- [ ] 1.3 Larastan nível 7, Pint e `npm run typecheck` (vue-tsc) configurados e passando no projeto vazio
- [ ] 1.4 2FA ativado no Fortify; registro de novos usuários desligado (um usuário criado por seeder)
- [ ] 1.5 Tokens do design system no tema do shadcn-vue (escuro como padrão), fontes Familjen Grotesk, Inter e IBM Plex Mono

## 2. Dados
- [ ] 2.1 Migration e model `BankConnection`
- [ ] 2.2 Migration e model `Account`
- [ ] 2.3 Migration e model `Transaction` com índice único em `pluggy_transaction_id`, enums `source`, `direction`, `status` e `pluggy_category` (texto, nulo); só as colunas desta mudança
- [ ] 2.4 Factories dos três models

## 3. Client do Pluggy
- [ ] 3.1 Ler a doc atual de `POST /auth`, `GET /items/{id}`, `GET /accounts` e `GET /transactions` (paginação) e anotar os endpoints no topo do service
- [ ] 3.2 `app/Services/Pluggy/PluggyClient.php` com auth em cache e os três métodos de leitura, todos testados com `Http::fake()`
- [ ] 3.3 Comando `bank:connect {itemId}` que cria a `BankConnection` com nome e logo do conector

## 4. Sync (testes primeiro)
- [ ] 4.1 Testes dos cenários de `specs/bank-sync/spec.md`, todos falhando
- [ ] 4.2 Conversão de valores (`amount` + `type` → `amount_cents` + `direction`)
- [ ] 4.3 Job `SyncBankConnection` com upsert, janela de datas e tratamento de falha
- [ ] 4.4 Comando `bank:sync` e agendamento a cada 4 horas
- [ ] 4.5 Todos os testes de 4.1 passando

## 5. Telas
- [ ] 5.1 Layout mobile com a barra de navegação inferior (Início, Transações, Compromissos, Bancos); Início e Compromissos como placeholders
- [ ] 5.2 Tela Transações seguindo o artboard Transações (sem os banners de conciliação)
- [ ] 5.3 Tela Bancos seguindo o artboard Bancos, com liga/desliga e "Sincronizar agora"
- [ ] 5.4 Teste de navegador do Pest: login → Bancos → Sincronizar agora (Pluggy falso) → Transações lista os itens

## 6. Fechamento
- [ ] 6.1 Rodar `bank:connect` com os dois item IDs reais e `bank:sync` contra o Pluggy de verdade
- [ ] 6.2 Conferir no app 5 transações recentes contra o app de cada banco
- [ ] 6.3 Responder no `design.md` as perguntas abertas (id de pendente, atraso real)
- [ ] 6.4 `composer test`, Pint, PHPStan e typecheck passando; arquivar a mudança
