# Plano de Testes (Feature)

1) **Batida simples (IN)**  
- Dado usuário `colaborador` autenticado, ao registrar `IN`, salva `ts_server` (UTC), `ip`, `user_agent`. View mostra sucesso.

2) **Negar geolocalização**  
- Ao negar, o registro é salvo com `geo=null`, `geo_consent=false`. Dashboard RH mostra flag `sem_geo`.

3) **Filtros do RH**  
- `rh_manager` acessa `/rh/ponto`, filtra por período/usuário/flag, vê resultados e paginação em < 2s (dataset simulado).

4) **Solicitação de ajuste**  
- `colaborador` cria ajuste PENDENTE. `rh_manager` aprova → status `APROVADO`, grava `approver_id` e `decided_at`.

5) **Export CSV**  
- Com filtros aplicados, exporta arquivo com colunas definidas e timezone correto.

6) **RBAC**  
- `colaborador` não acessa rotas `/rh/*`. `rh_manager` sim. `admin` também.

7) **Intervalo mínimo**  
- Rejeitar batida se ocorrer dentro do intervalo mínimo configurado (ex.: 2 minutos) desde a última batida do usuário.
