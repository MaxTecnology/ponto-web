# PROGRESS.md — Checkpoints e Relatórios de Etapas

Este documento define **como o gerador deve reportar progresso** ao concluir cada etapa do `TASKS.md`.  
Use os **marcadores padronizados** abaixo para facilitar revisão automática.

## Formato de relatório por etapa
Ao finalizar **cada** etapa, imprimir no output um bloco como este (sem executar comandos):

```
[CHECKPOINT: {n}/{total}]
ETAPA: {nome-da-etapa}
STATUS: CONCLUIDA

ARQUIVOS_CRIADOS:
- caminho/para/arquivo1
- caminho/para/arquivo2

ARQUIVOS_ALTERADOS:
- caminho/para/arquivo3
- caminho/para/arquivo4

RESUMO_MUDANCAS:
- Breve descrição do que foi implementado (classes, migrations, rotas, componentes).
- Referência aos itens correspondentes nos .md (ex.: DB_SCHEMA.md §punches).

PROXIMOS_PASSOS (NAO EXECUTAR):
- Comando sugerido 1 (ex.: ./vendor/bin/sail artisan migrate)
- Comando sugerido 2 (ex.: ./vendor/bin/sail npm run dev)

TIMESTAMP_UTC: 2025-09-25T00:00:00Z
```

### Tratamento de exceções
Se houver impedimento (arquivo faltando, conflito), **não prosseguir**. Em vez de `CONCLUIDA`, usar:
```
[CHECKPOINT: {n}/{total}]
ETAPA: {nome-da-etapa}
STATUS: BLOQUEADA
MOTIVO: Descrever claramente o problema (ex.: migração duplicada, referência inexistente).
ARQUIVO_AFETADO: caminho/para/arquivo
SUGESTAO_RESOLUCAO: Passos para destravar.
```

## Mapa de etapas (alinhar com TASKS.md)
1. **Instalação & Setup** — Dependências (Livewire 3, Breeze), Tailwind, `.env` com MySQL e `APP_TIMEZONE=America/Maceio`.
2. **RBAC** — Migration `users.role` + índice; Gate `view-rh` (`rh_manager` e `admin`).
3. **Migrations & Models** — `punches`, `adjust_requests`, `holidays`, `settings` com casts/relacionamentos.
4. **Seeders** — `rh_manager`, `colaborador`, feriados e `settings` (`min_interval_minutes=2`).
5. **Rotas** — `routes/web.php` conforme `ROUTES.md` (todas `auth`; RH com `can:view-rh`). 
6. **Livewire — Ponto** — `Ponto/BaterPonto` + view com coleta de geo/device/fingerprint e consentimento.
7. **Livewire — Espelho** — `Ponto/MeuEspelho` (mês atual, totais simples, flags).
8. **Livewire — RH/Dashboard** — filtros, paginação, flags (`sem_geo`, `ip_novo`, `fingerprint_novo`).
9. **Livewire — RH/Ajustes** — CRUD + aprovar/rejeitar com auditoria simples.
10. **Livewire — RH/Fechamento** — fechamento lógico + export **CSV** conforme `EXPORT_CSV.md`.
11. **Helpers de Timezone** — conversões UTC ↔ America/Maceio usados nas views/CSV.
12. **Testes de Feature** — cenários do `TEST_PLAN.md` (batida, negar geo, filtros RH, ajuste, export, RBAC, intervalo mínimo).

**total = 12**.

## Relatório final (após etapa 12)
Imprimir um sumário final:
```
[COMPLETED]
ETAPAS_CONCLUIDAS: 12/12
ARQUIVOS_CRIADOS: N
ARQUIVOS_ALTERADOS: M

RESUMO_POR_TOPICO:
- Migrations/Models: ...
- Livewire/Views: ...
- Rotas/RBAC: ...
- CSV/Timezone: ...
- Testes: ...

INSTRUCOES_POS_GERACAO (NAO EXECUTAR):
- ./vendor/bin/sail up -d
- ./vendor/bin/sail artisan migrate --seed
- ./vendor/bin/sail npm run dev
```

> Observação: **não executar** comandos; apenas **listar** os comandos pro usuário executar manualmente.
