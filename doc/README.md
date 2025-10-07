# Sistema de Ponto Web — Especificação para Gerador de Código
Este pacote de arquivos `.md` é para ser **lido pelo gerador de código (Codex)**. Ele descreve **o que criar**, **como organizar** e **os requisitos funcionais** do MVP.

> **Stack alvo:** Laravel 11, PHP 8.3, Blade + Livewire 3, MySQL (via Sail), Redis (reservado), Tailwind (Breeze).  
> **Escopo:** Bater ponto (colaborador) + Painel RH (listar/filtrar/ajustes/fechamento/export CSV).  
> **Sem SPA e sem API pública** no MVP. Tudo server-rendered com Livewire.

## Ordem sugerida de leitura/execução pelo gerador
1. `ARCHITECTURE.md` — estrutura do projeto, dependências, RBAC, configuração.
2. `DB_SCHEMA.md` — migrations, índices, casts e seeds.
3. `ROUTES.md` — rotas protegidas + guards/gates.
4. `LIVEWIRE_COMPONENTS.md` — componentes e comportamento.
5. `CONSENT_LGPD.md` — consentimento de geolocalização e privacidade.
6. `EXPORT_CSV.md` — formato de exportação e conversão de timezone.
7. `TEST_PLAN.md` — testes de feature e de fluxo.
8. `PROGRESS.md` — formato de checkpoints e como reportar cada etapa.
9. `TASKS.md` — lista de tarefas que o gerador deve executar (criar/editar arquivos).
10. `CHECKLIST_DE_DEPLOY.md` — **(produção)**: publicar com Docker, Nginx+TLS, backups, cron, rollback.

### Materiais de apoio
- `GLOSSARIO.md` — referência de termos de negócio/técnicos (pode ser lido a qualquer momento).

**Resultado esperado:** o gerador cria um projeto Laravel funcional com tudo descrito, incluindo migrations, models, views Blade/Livewire, policies/gates, seeders e testes, pronto para:
- `sail up -d`
- `sail artisan migrate --seed`
- `sail npm run dev`

> Dica ao Codex: seguir **estritamente** a ordem acima para não se perder, e executar as tarefas de `TASKS.md` na sequência. Em caso de conflito, prevalecem as definições de `DB_SCHEMA.md` e `ROUTES.md`.
