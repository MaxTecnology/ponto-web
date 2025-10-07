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
- Filtros: período (data início/fim), usuário (search), tipo + toggles rápidos para `ip_novo` e `fingerprint_novo`.
- Mostra banners/resumo com contagem de batidas, colaboradores, % geo e alertas ativos.
- Tabela paginada: usuário, tipo, `ts_local`, IP, resumo device/geo, flags (incluindo `sem_geo` para histórico).
- Ação **Exportar CSV** mantendo filtros aplicados.

## Rh\Ajustes
- Cards com métricas (pendentes/aprovados/rejeitados, tempo médio).
- Lista `PENDENTE` com destaque para seleção, formulário inline e histórico do ajuste.
- Aprovar/Rejeitar com comentário (gravar `approver_id`, `decided_at`, `audit`).
- Histórico recente das decisões (aprovados/rejeitados) com comentário final e executor.

## Rh\Fechamento
- Selecionar período, ver cards com ajustes pendentes, dias sem saída, período e último fechamento.
- Pendências detalhadas (dias sem saída) com link rápido para dashboard/ajustes.
- Timeline com histórico de fechamentos anteriores.
- Botão **Fechar período** (snapshot lógico) e exportação CSV.

## Flags automáticas (lógica básica)
- `ip_novo`: comparar IP atual com IPs do mesmo usuário nos últimos 30 dias.
- `fingerprint_novo`: primeiro uso do hash para o usuário.
- `sem_geo`: `geo == null`.
