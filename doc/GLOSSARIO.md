# GLOSSARIO.md — Termos de Produto e Técnicos

### Batida
Registro pontual de tempo: **Entrada (IN)**, **Saída (OUT)**, **Início de Pausa (BREAK_IN)**, **Fim de Pausa (BREAK_OUT)**.

### Espelho de Ponto
Visão mensal do colaborador com todas as batidas do período e totais simples (no MVP).

### Ajuste de Ponto
Solicitação para corrigir batidas (ex.: esqueci de sair). Fluxo: **PENDENTE → APROVADO/REJEITADO** com trilha mínima.

### Fechamento
Processo de consolidar um período (ex.: mês) para conferência/exportação. No MVP é **lógico** (não tranca o banco).

### Flags (Antifraude Leve)
- **sem_geo**: batida sem geolocalização (usuário negou ou indisponível).
- **ip_novo**: IP não visto para o usuário nos últimos 30 dias.
- **fingerprint_novo**: hash de dispositivo inédito para o usuário.
- **fora_endereco** (futuro): batida fora do raio do endereço de home office.

### Geolocalização (geo) e Consentimento
Dados `{lat, lon, accuracy_m}` coletados pelo navegador **com consentimento**. Se negado, batida é registrada com flag.

### `ts_server` (UTC) e `ts_local`
- `ts_server`: timestamp gravado no **servidor** (UTC) — autoridade.
- `ts_local`: `ts_server` convertido para **America/Maceio** na exibição/CSV.

### Timezone/Fuso
Armazenar em **UTC**; exibir em **America/Maceio**. Evita inconsistências entre dispositivos.

### RBAC (Papéis)
- **colaborador**: bate ponto e vê seu espelho.
- **rh_manager**: vê tudo, filtra, aprova ajustes, fecha e exporta.
- **admin**: gerencia papéis/usuários e tem acesso às telas do RH.

### Tolerância e Janela de Batida
- **Tolerância**: margem configurável para atraso/adiantamento exibida no espelho.
- **Janela mínima**: intervalo (ex.: 2min) entre batidas consecutivas — impede cliques acidentais.

### Jornada/Core Hours (futuro)
Regras de horários por contrato (fixa, flexível, 12x36) e janela de horas nucleares de trabalho.

### Banco de Horas (futuro)
Módulo para compensação de horas, limites e autorizações. **Fora do MVP**.

### Adicional Noturno (futuro)
Cálculo de horas noturnas conforme legislação. **Fora do MVP**.

### Fingerprint (Dispositivo)
Hash (ex.: SHA-256) de atributos do navegador/dispositivo (UA, plataforma, tamanho de tela, timezone, etc.) usado para sinalizar troca de dispositivo.

### `device_info`
JSON com metadados do ambiente do usuário: `{platform, language, screen:{w,h}, timezone}`.

### RPA / Integrações (futuro)
Automação de processos (folha, ERP) via **API/Webhooks/Exports**. Fora do MVP; manter ganchos.

### Export CSV
Arquivo com as colunas padronizadas (ver `EXPORT_CSV.md`) respeitando filtros e convertendo timezone corretamente.

### Retenção
Prazos de guarda: geo detalhado por **6–12 meses**; registros de ponto/auditoria por **5 anos** (ajuste conforme jurídico).

### LGPD — Bases e Princípios
- **Base legal**: consentimento (geo) e execução de contrato (registro de ponto).
- **Minimização**: coletar só o necessário; restringir acesso a dados sensíveis.
- **Transparência**: modal/texto explicando finalidade e prazos.
- **Segurança**: criptografia em trânsito (HTTPS), controle de acesso, logs.

### Auditoria (futuro)
Trilha imutável com **hash encadeado** para mudanças sensíveis (ajustes). Planejado para fase posterior.

### SSO / Webhooks (futuro)
Integração com IdPs corporativos (Azure AD/Google) e eventos para integrações. Fora do MVP.
