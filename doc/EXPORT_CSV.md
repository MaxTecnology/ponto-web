# Exportação CSV

## Endpoint
- `GET /rh/export` (autenticado + `can:view-rh`).  
- Respeitar filtros ativos do Dashboard.

## Colunas
- `punch_id`
- `user_id`
- `nome`
- `email`
- `role`
- `data_local` (YYYY-MM-DD)
- `ts_server_utc` (ISO 8601)
- `ts_local` (ISO 8601, America/Maceio)
- `tipo`
- `ip`
- `user_agent`
- `geo_lat`
- `geo_lon`
- `accuracy_m`
- `sem_geo` (true/false)
- `ip_novo` (true/false)
- `fingerprint_novo` (true/false)
- `observacao`

## Conversão de timezone
- Converter `ts_server` (UTC) para `America/Maceio` antes de preencher `ts_local` e `data_local`.
