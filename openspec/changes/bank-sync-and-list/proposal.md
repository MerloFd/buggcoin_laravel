# Proposta: bank-sync-and-list

## Por quê

O problema central é gastar demais sem enxergar. Antes de qualquer gráfico, as transações reais dos dois bancos precisam entrar no app sozinhas. Esta é a fatia que prova que isso funciona: ao fim dela, abro o app e vejo o meu dinheiro.

## O que muda

- Projeto criado a partir do starter kit oficial de Vue do Laravel 13, com MySQL no Docker para desenvolvimento.
- Login com 2FA (Fortify) para um único usuário.
- Tabelas `bank_connections`, `accounts` e `transactions`.
- Client do Pluggy em `app/Services/Pluggy/`, autenticando só no servidor.
- Comando `bank:sync` e job `SyncBankConnection`, agendados a cada 4 horas e disparáveis pelo botão "Sincronizar agora".
- Tela Transações: lista do mês com origem visível e filtro Todas / Do banco / Manuais (o filtro Manuais fica vazio até a próxima mudança).
- Tela Bancos: as conexões, a última sincronização, o liga/desliga do sync, o botão "Sincronizar agora" e o botão "Adicionar banco" (colar o item ID do Meu Pluggy).

## Fora do escopo

- Lançamento manual (mudança `manual-entries`).
- Conciliação de duplicata e transferência (mudança `reconciliation`).
- Início, limite do mês e linha do mês (mudança `home-dashboard`).
- Faturas e parcelas (mudança `commitments`).
- Webhooks do Pluggy, PWA e deploy.
- Widget Pluggy Connect (conectar o banco sem sair do app): depende de confirmar se o Meu Pluggy permite.
- Categorias próprias: nesta mudança, a categoria do Pluggy aparece como texto.

## Impacto

- Novas capacidades: `bank-sync`, `transactions-list`.
- Dependências: nenhuma além do starter kit. O Pluggy é chamado com o client HTTP do Laravel.
- Pré-requisito fora do código: os dois bancos já conectados no meu.pluggy.ai, com os item IDs em mãos.
