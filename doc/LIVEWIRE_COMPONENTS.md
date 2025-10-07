# Livewire — Componentes e Comportamentos

## Ponto\BaterPonto
- Mostra status do dia (última batida com hora local).
- Select de tipo: `IN`, `OUT`, `BREAK_IN`, `BREAK_OUT`.
- Campo opcional de observação (até 255 chars).
- Botão **Bater Ponto**:
  - Exibir modal de **consentimento** (se ainda não dado na sessão).
  - Tentar `navigator.geolocation` (timeout 7s); se falhar, `geo=null`.
  - Coletar `userAgent`, `platform`, `language`, `screen(w,h)`, `timezone`.
  - Calcular `fingerprint_hash` (SHA-256).
  - Enviar payload p/ ação Livewire `salvar(payload)`.
- No backend:
  - `ts_server = now()` (UTC); `ip = request()->ip()`; `user_agent = request()->userAgent()`.
  - Validar tipo, intervalo mínimo entre batidas (usar setting `min_interval_minutes`).
  - Salvar flags derivadas (ex.: `sem_geo` apenas como visualização via `geo==null`).
  - Emitir toast de sucesso/erro.

## Ponto\MeuEspelho
- Selecionar mês/ano.
- Exibir dias com batidas (local time), total bruto (sem banco de horas).
- Ícones de flags: `sem_geo`, `ip_novo`, `fingerprint_novo`.
- Form para **Solicitar Ajuste** (cria registro pendente).

## Rh\Dashboard
- Filtros: período (data início/fim), usuário (search), tipo, flags (`sem_geo`, `ip_novo`, `fingerprint_novo`).
- Tabela paginada: usuário, tipo, `ts_local`, IP, resumo device/geo, flags.
- Ação **Exportar CSV** mantendo filtros.

## Rh\Ajustes
- Lista `PENDENTE`.
- Aprovar/Rejeitar com comentário (gravar `approver_id`, `decided_at`, `audit`).

## Rh\Fechamento
- Selecionar período.
- Mostrar pendências (ajustes pendentes, dias sem saída).
- Botão **Fechar período** (snapshot lógico).
- Botão **Exportar CSV**.

## Flags automáticas (lógica básica)
- `ip_novo`: comparar IP atual com IPs do mesmo usuário nos últimos 30 dias.
- `fingerprint_novo`: primeiro uso do hash para o usuário.
- `sem_geo`: `geo == null`.
