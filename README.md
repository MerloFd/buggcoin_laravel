# BuggCoin

App pessoal de finanças, de um único usuário, que responde três perguntas:

- Quanto gastei no mês?
- Quanto ainda posso gastar?
- Quanto devo nos próximos meses?

A proposta é que as transações cheguem sozinhas dos bancos via [Meu Pluggy](https://meu.pluggy.ai) (Open Finance), com lançamento manual como complemento.

## Status

Em desenvolvimento. A mudança em andamento é a `bank-sync-and-list`.

- [x] Fundação: login com 2FA e chaves de acesso, tema do design system, interface em pt-BR
- [x] Modelo de dados: conexões, contas e transações
- [ ] Client do Pluggy e sincronização
- [ ] Telas Transações e Bancos
- [ ] Lançamento manual, conciliação, início, compromissos e PWA

## Stack

| Camada    | Tecnologias                                            |
| --------- | ------------------------------------------------------ |
| Back-end  | PHP 8.4, Laravel 13, Fortify, Wayfinder                |
| Front-end | Inertia 3, Vue 3.5, TypeScript, Tailwind 4, shadcn-vue |
| Dados     | MySQL 8.4; fila, cache e sessão no próprio banco       |
| Qualidade | Pest 5, Pint, Larastan nível 7, vue-tsc                |

## Como rodar

Requer PHP 8.4 (com `pdo_mysql` e `pdo_sqlite`), Composer, Node 22+ e Docker.

1. Copie `.env.example` para `.env` e preencha `DB_PASSWORD` e as três variáveis `OWNER_*`.
2. Suba o banco e instale o projeto:

    ```bash
    docker compose up -d --wait
    composer setup
    php artisan db:seed
    composer dev
    ```

O cadastro é desativado: o único usuário é criado pelo seeder. As credenciais do Pluggy ficam só no servidor e ainda não são necessárias.

## Qualidade

```bash
composer test
npm run types:check
npm run check
```

Os testes rodam em SQLite em memória.

## Decisões de projeto

- Dinheiro em centavos inteiros, sempre positivo, com a direção em coluna própria. A única exceção é o saldo da conta.
- A sincronização é idempotente: um índice único impede transações duplicadas.
- A conciliação é sempre sugerida e confirmada pelo usuário.

## Processo

O desenvolvimento é orientado a especificações com [OpenSpec](https://github.com/Fission-AI/OpenSpec) (veja `openspec/`) e feito com apoio do Claude Code. As regras de trabalho estão no `CLAUDE.md`; os links do claude.ai citados nele são documentação privada.
