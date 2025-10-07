# Banco de Dados — Migrations & Seeds (MySQL)

## Alterar usuários (role / status)
- Migration para adicionar `role VARCHAR(32) DEFAULT 'colaborador'` em `users` + índice.
- Campo `deactivated_at TIMESTAMP NULL` em `users` para bloquear login quando preenchido.

## Tabela `punches`
Campos:
- `id BIGINT PK`
- `user_id BIGINT FK -> users`
- `type ENUM('IN','OUT','BREAK_IN','BREAK_OUT')`
- `ts_server DATETIME(6)` (UTC)
- `ts_client DATETIME(6) NULL`
- `ip VARCHAR(45)`
- `user_agent TEXT NULL`
- `device_info JSON NULL` (platform, language, screen(w,h), timezone)
- `fingerprint_hash CHAR(64) NULL INDEX`
- `geo JSON NULL` ({lat, lon, accuracy_m})
- `geo_consent BOOLEAN DEFAULT 0`
- `observacao VARCHAR(255) NULL`
- `source VARCHAR(16) DEFAULT 'web'`
- Índices: `(user_id, ts_server)`, `fingerprint_hash`

## Tabela `adjust_requests`
- `id PK`
- `user_id FK`
- `date DATE` (referência do dia ajustado)
- `from_ts DATETIME(6) NULL`
- `to_ts DATETIME(6) NULL`
- `reason TEXT`
- `status ENUM('PENDENTE','APROVADO','REJEITADO') DEFAULT 'PENDENTE'`
- `approver_id FK NULL`
- `decided_at DATETIME(6) NULL`
- `audit JSON NULL`
- `timestamps`

## Tabela `holidays`
- `id PK`
- `scope ENUM('NACIONAL','UF','MUNICIPIO','EMPRESA')`
- `uf CHAR(2) NULL`
- `municipio VARCHAR(100) NULL`
- `date DATE`
- `name VARCHAR(100)`

## Tabela `settings`
- `key VARCHAR(64) PK`
- `value JSON`
- Registro esperado:
  - `key = 'ponto'` → `{ "min_interval_minutes": 2 }`
  - `key = 'ponto_fechamentos'` → lista de períodos fechados logicamente.

## Seeds
- Usuário `rh_manager` (rh@example.com / secret123)
- Usuário `colaborador` (colab@example.com / secret123)
- 2–3 feriados nacionais.
- `settings`: `{ "min_interval_minutes": 2 }`

## Observações
- Definir casts no Model `Punch`: `device_info` e `geo` como `array`, timestamps.
- Garantir que `now()` salve UTC.
