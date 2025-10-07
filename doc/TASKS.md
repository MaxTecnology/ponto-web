# Tarefas para o Gerador de Código (Executar na ordem)

1. **Instalação**
   - Adicionar Livewire 3 e Breeze (Blade).
   - Garantir Tailwind funcionando.
   - Ajustar `.env` para MySQL (Sail) e `APP_TIMEZONE=America/Maceio`.

2. **RBAC**
   - Migration: adicionar `role` em `users` + índice.
   - Gate `view-rh` (users com role `rh_manager` ou `admin`).

3. **Migrations e Models**
   - Criar `punches`, `adjust_requests`, `holidays`, `settings` conforme `DB_SCHEMA.md`.
   - Models com casts e relacionamentos.

4. **Seeders**
   - Usuários de exemplo (`rh_manager`, `colaborador`), feriados e settings.

5. **Rotas**
   - Implementar rotas conforme `ROUTES.md` com `auth` e `can:view-rh`.

6. **Livewire**
   - Criar componentes e views conforme `LIVEWIRE_COMPONENTS.md` (com JS embutido para geo/device/fingerprint e modal de consentimento).

7. **Helpers de Timezone**
   - Funções para converter UTC↔America/Maceio nas views e no CSV.

8. **Dashboard RH + Filtros**
   - Implementar filtros, paginação, cálculo de flags (`ip_novo`, `fingerprint_novo`, `sem_geo`).

9. **Ajustes**
   - CRUD de solicitações, aprovação/rejeição com auditoria simples.

10. **Fechamento & Export**
   - Página de fechamento (snapshot lógico) e **/rh/export** gerando CSV conforme `EXPORT_CSV.md`.

11. **Restrições de Privacidade**
   - Modal e persistência de `geo_consent`. Restringir exibição de GEO no RH/Admin.

12. **Testes**
   - Implementar os cenários listados em `TEST_PLAN.md`.

**Entrega final:** projeto rodando em dev com `sail up -d`, `sail artisan migrate --seed`, `sail npm run dev`. Exibir menu para Colaborador (Ponto/Espelho) e RH (Dashboard/Ajustes/Fechamento/Export).
