# Laravel Async Queues: Estratégias Comparativas

> **Projeto Educacional**: Implementação de diferentes estratégias de filas assíncronas no Laravel para comparação e estudo de arquiteturas de queue processing.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Projetos Incluídos](#projetos-incluídos)
3. [Instalação e Setup](#instalação-e-setup)
4. [Como Testar](#como-testar)
5. [Comparação de Estratégias](#comparação-de-estratégias)
6. [Conceitos Aplicados](#conceitos-aplicados)
7. [Análise de Trade-offs](#análise-de-trade-offs)
8. [Cenários de Uso](#cenários-de-uso)
9. [Recursos de Aprendizado](#recursos-de-aprendizado)

---

## 🎯 Visão Geral


Este repositório contém duas implementações distintas de **filas assíncronas** no Laravel, cada uma seguindo uma estratégia arquitetural diferente:

1. **supervisor-horizon**: Container único com Supervisor gerenciando Horizon
2. **docker-queue-balance**: Containers separados com balanceamento automático

O objetivo é demonstrar e comparar diferentes abordagens para processamento assíncrono, permitindo análise prática de trade-offs arquiteturais.

### Dataset de Teste

Ambos os projetos processam dados climáticos em CSV:
- `data.csv`: 1000+ registros de temperatura
- `low-size-data.csv`: 99 registros para testes rápidos

---

## 📦 Projetos Incluídos

### 1. Supervisor + Horizon (`supervisor-horizon/`)

**Estratégia**: Monolito gerenciado por Supervisor

```
┌─────────────────────────────────────┐
│           Container Laravel         │
│  ┌─────────────────────────────────┐ │
│  │         Supervisor              │ │
│  │  ┌─────────────┐ ┌────────────┐ │ │
│  │  │   Horizon   │ │ Laravel    │ │ │
│  │  │   Workers   │ │   Serve    │ │ │
│  │  └─────────────┘ └────────────┘ │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
         │                    │
    ┌─────────┐         ┌──────────┐
    │  Redis  │         │  SQLite  │
    │ (Queue) │         │  (Data)  │
    └─────────┘         └──────────┘
```

**Características**:
- ✅ Setup simples e rápido
- ✅ Dashboard integrado (Horizon)
- ✅ Ideal para desenvolvimento
- ❌ Escalabilidade limitada

### 2. Docker Queue Balance (`docker-queue-balance/`)

**Estratégia**: Microservices com containers especializados

```
┌────────────┐  ┌─────────────┐  ┌─────────────┐
│    Web     │  │   Worker    │  │   Worker    │
│ Container  │  │ Container 1 │  │ Container 2 │
│            │  │             │  │             │
│ HTTP APIs  │  │ Queue:high  │  │Queue:default│
└────────────┘  └─────────────┘  └─────────────┘
       │               │               │
       └───────────────┼───────────────┘
                       │
               ┌───────────────┐
               │     Redis     │
               │ (Message Bus) │
               └───────────────┘
                       │
               ┌───────────────┐
               │   Database    │
               │   (MySQL)     │
               └───────────────┘
```

**Características**:
- ✅ Escalabilidade horizontal
- ✅ Isolamento de recursos
- ✅ Fault tolerance
- ❌ Maior complexidade

---

## 🚀 Instalação e Setup

### Pré-requisitos

```bash
# Verificar se tem Docker e Docker Compose
docker --version          # >= 20.10
docker-compose --version  # >= 2.0
```

### Clone do Repositório

```bash
git clone <repository-url>
cd laravel-async-queues
```

### Setup do Projeto 1: Supervisor + Horizon

```bash
# Navegar para o projeto
cd supervisor-horizon/

# Subir containers
docker-compose up -d

# Verificar se containers estão rodando
docker-compose ps

# Verificar logs do Supervisor
docker-compose logs -f app
```

**Serviços disponíveis:**
- 🌐 **Laravel App**: http://localhost:8000
- 📊 **Horizon Dashboard**: http://localhost:8000/horizon
- 🔴 **Redis**: localhost:6380

### Setup do Projeto 2: Docker Queue Balance

```bash
# Navegar para o projeto
cd docker-queue-balance/

# Subir containers
docker-compose up -d

# Verificar se containers estão rodando
docker-compose ps

# Verificar logs dos workers
docker-compose logs -f worker-high-priority
docker-compose logs -f worker-default
```

**Serviços disponíveis:**
- 🌐 **Laravel App**: http://localhost:8001
- 🔴 **Redis**: localhost:6381
- 🗄️ **MySQL**: localhost:3307

---

## 🧪 Como Testar

### Teste 1: Supervisor + Horizon

```bash
cd supervisor-horizon/

# 1. Processar dataset pequeno
docker-compose exec app php artisan process:csv-data --file=low-size-data.csv --batch-size=2

# 2. Monitorar via Horizon Dashboard
# Abrir: http://localhost:8000/horizon

# 3. Verificar dados salvos
docker-compose exec app php artisan tinker --execute="
echo 'Total records: ' . App\Models\TemperatureReading::count();
"

# 4. Processar dataset grande
docker-compose exec app php artisan process:csv-data --file=../data.csv --batch-size=10

# 5. Análise de performance
# - Acessar Horizon → Metrics
# - Observar throughput e latência
# - Verificar utilização de workers
```

### Teste 2: Docker Queue Balance

```bash
cd docker-queue-balance/

# 1. Processar com balanceamento automático
docker-compose exec web php artisan process:csv-data --file=../low-size-data.csv --batch-size=3

# 2. Monitorar workers separadamente
# Terminal 1:
docker-compose logs -f worker-high-priority

# Terminal 2:
docker-compose logs -f worker-default

# 3. Verificar distribuição de carga
docker-compose exec web php artisan queue:monitor

# 4. Teste de escalabilidade
docker-compose up -d --scale worker-default=3

# 5. Processar dataset grande
docker-compose exec web php artisan process:csv-data --file=../data.csv --batch-size=5
```

### Testes de Stress

```bash
# Teste 1: Alto volume de jobs pequenos
--batch-size=1  # Gera mais jobs

# Teste 2: Jobs pesados
--batch-size=50 # Menos jobs, mais dados por job

# Teste 3: Concorrência
# Executar múltiplos comandos simultaneamente
```

### Verificação de Resultados

```bash
# Supervisor + Horizon
cd supervisor-horizon/
docker-compose exec app php artisan tinker --execute="
\$total = App\Models\TemperatureReading::count();
\$avg = App\Models\TemperatureReading::avg('temperatura');
echo \"Total: \$total | Avg Temp: \$avg°C\";
"

# Docker Queue Balance
cd docker-queue-balance/
docker-compose exec web php artisan tinker --execute="
\$total = App\Models\TemperatureReading::count();
\$avg = App\Models\TemperatureReading::avg('temperatura');
echo \"Total: \$total | Avg Temp: \$avg°C\";
"
```

---

## ⚖️ Comparação de Estratégias

### Características Técnicas

| Aspecto | Supervisor + Horizon | Docker Queue Balance |
|---------|---------------------|---------------------|
| **Arquitetura** | Monolítica | Microservices |
| **Containers** | 2 (app + redis) | 5+ (web + workers + db + redis) |
| **Escalabilidade** | Vertical apenas | Horizontal + Vertical |
| **Isolamento** | Processos compartilhados | Containers isolados |
| **Dashboard** | Horizon nativo | Logs distribuídos |
| **Persistência** | SQLite | MySQL |
| **Complexidade Setup** | 🟢 Baixa | 🟡 Média |
| **Troubleshooting** | 🟢 Simples | 🔴 Complexo |

### Performance

| Métrica | Supervisor + Horizon | Docker Queue Balance |
|---------|---------------------|---------------------|
| **Time to Start** | ~30s | ~60s |
| **Memory Usage** | ~200MB | ~400MB |
| **Throughput** | 50-100 jobs/min | 100-300 jobs/min |
| **Latência** | Baixa | Média |
| **Resource Efficiency** | 🟡 Média | 🟢 Alta |

### Cenários Ideais

#### Supervisor + Horizon ✅
- **Desenvolvimento local**
- **Prototipagem rápida**
- **Aplicações pequenas-médias**
- **Equipes pequenas**
- **Budget limitado**

#### Docker Queue Balance ✅
- **Produção enterprise**
- **Alto volume de dados**
- **Scaling requirements**
- **Fault tolerance crítica**
- **Equipes DevOps maduras**

---

## 📚 Conceitos Aplicados

### 1. Queue Processing Patterns

#### **Producer-Consumer Pattern**
```php
// Producer (Controller/Command)
ProcessCsvDataJob::dispatch($data);

// Consumer (Worker)
class ProcessCsvDataJob {
    public function handle() { /* process */ }
}
```

#### **Batch Processing**
```php
// Chunking para otimização
$batches = array_chunk($csvData, $batchSize);
foreach ($batches as $batch) {
    ProcessCsvDataJob::dispatch($batch);
}
```

### 2. Container Orchestration

#### **Single Container Strategy**
```yaml
# supervisor-horizon
services:
  app:        # Laravel + Supervisor + Horizon
  redis:      # Message broker
```

#### **Multi-Container Strategy**
```yaml
# docker-queue-balance
services:
  web:                    # HTTP interface
  worker-high-priority:   # Critical jobs
  worker-default:         # Normal jobs
  worker-low-priority:    # Background jobs
  redis:                  # Message broker
  mysql:                  # Data persistence
```

### 3. Load Balancing Strategies

#### **Auto-balancing (Horizon)**
```php
'balance' => 'auto',
'autoScalingStrategy' => 'time',
'maxProcesses' => 5,
```

#### **Manual Queue Assignment**
```php
// Distribuição manual por prioridade
ProcessCsvDataJob::dispatch($data)->onQueue('high-priority');
ProcessCsvDataJob::dispatch($data)->onQueue('default');
ProcessCsvDataJob::dispatch($data)->onQueue('low-priority');
```

### 4. Monitoring & Observability

#### **Centralized (Horizon)**
- Dashboard único
- Métricas agregadas
- Real-time monitoring

#### **Distributed (Docker Logs)**
- Logs por container
- Agregação manual necessária
- Tools externos para observabilidade

### 5. Data Persistence Patterns

#### **Local File Storage (SQLite)**
```php
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite
```

#### **Network Database (MySQL)**
```php
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=laravel_queues
```

### 6. Process Management

#### **Supervisor Process Control**
```ini
[program:laravel-horizon]
command=php artisan horizon
autostart=true
autorestart=true
```

#### **Docker Container Lifecycle**
```bash
# Scaling workers
docker-compose up -d --scale worker-default=3

# Health checks
healthcheck:
  test: ["CMD", "php", "artisan", "queue:monitor"]
```

---

## 🔄 Análise de Trade-offs

### Development Experience

| Aspecto | Supervisor + Horizon | Docker Queue Balance |
|---------|---------------------|---------------------|
| **Tempo de Setup** | 🟢 5 minutos | 🟡 15 minutos |
| **Hot Reload** | 🟢 Simples | 🟡 Rebuild containers |
| **Debugging** | 🟢 Um processo | 🔴 Múltiplos containers |
| **Local Testing** | 🟢 Ideal | 🟡 Resource intensive |

### Production Readiness

| Aspecto | Supervisor + Horizon | Docker Queue Balance |
|---------|---------------------|---------------------|
| **Fault Tolerance** | 🔴 Single point failure | 🟢 Distributed |
| **Scaling** | 🔴 Vertical only | 🟢 Horizontal |
| **Resource Isolation** | 🔴 Shared | 🟢 Isolated |
| **Monitoring** | 🟢 Built-in | 🟡 External tools needed |

### Operational Complexity

| Aspecto | Supervisor + Horizon | Docker Queue Balance |
|---------|---------------------|---------------------|
| **Deployment** | 🟢 Single unit | 🔴 Coordinated deploy |
| **Rollback** | 🟢 Simple | 🔴 Complex |
| **Backup Strategy** | 🟢 File-based | 🟡 Database-based |
| **Security** | 🟡 Shared container | 🟢 Network isolation |

---

## 🎯 Cenários de Uso

### Startup/MVP (Supervisor + Horizon)

```bash
# Cenário: E-commerce pequeno processando pedidos
# Volume: 100-1000 pedidos/dia
# Team: 2-5 desenvolvedores
# Budget: Limitado

Benefícios:
✅ Deploy rápido
✅ Costs baixos
✅ Simplicidade operacional
✅ Dashboard out-of-the-box
```

### Empresa Média (Híbrido)

```bash
# Cenário: SaaS B2B com múltiplos clientes
# Volume: 10k-100k jobs/dia
# Team: 10-20 desenvolvedores
# Budget: Médio

Estratégia:
🔄 Dev: Supervisor + Horizon
🔄 Staging: Docker Queue Balance
🔄 Prod: Kubernetes + Horizon
```

### Enterprise (Docker Queue Balance)

```bash
# Cenário: Plataforma de análise de dados
# Volume: 1M+ jobs/dia
# Team: 50+ desenvolvedores
# Budget: Alto

Benefícios:
✅ Auto-scaling
✅ Multi-region deployment
✅ Disaster recovery
✅ Compliance & security
```

### Casos Específicos

#### **Data Processing Pipeline**
```php
// Volume alto, processamento pesado
docker-queue-balance/ ← Escolha ideal
- Workers especializados
- Scaling horizontal
- Fault tolerance
```

#### **Real-time Notifications**
```php
// Latência baixa, volume médio
supervisor-horizon/ ← Escolha ideal
- Response time baixo
- Simplicidade
- Dashboard monitoring
```

#### **Background Analytics**
```php
// Processamento batch, não-crítico
supervisor-horizon/ ← Escolha ideal
- Cost-effective
- Setup simples
- Monitoring integrado
```

---

## 📈 Recursos de Aprendizado

### Hands-on Exercises

#### **Exercício 1: Performance Comparison**
```bash
# 1. Processar mesmo dataset em ambos projetos
# 2. Medir tempo de execução
# 3. Comparar resource usage
# 4. Analisar throughput

# Métricas a coletar:
- Jobs processed per minute
- Memory usage peak
- CPU utilization
- Error rate
```

#### **Exercício 2: Failure Scenarios**
```bash
# 1. Simular falhas de container
docker-compose stop redis

# 2. Observar comportamento
# 3. Testar recovery
# 4. Documentar lessons learned
```

#### **Exercício 3: Scaling Tests**
```bash
# 1. Aumentar workers gradualmente
# 2. Medir impact na performance
# 3. Identificar bottlenecks
# 4. Determinar optimal configuration
```

### Conceitos para Aprofundar

#### **Message Queue Theory**
- AMQP vs Redis Streams
- At-least-once vs Exactly-once delivery
- Message ordering guarantees
- Dead letter queues

#### **Container Orchestration**
- Docker Swarm vs Kubernetes
- Service discovery
- Load balancing algorithms
- Health checks & rolling deployments

#### **Distributed Systems**
- CAP Theorem aplicado
- Eventual consistency
- Circuit breaker pattern
- Bulkhead isolation

#### **Observability**
- Metrics vs Logs vs Traces
- SLIs, SLOs, SLAs
- Error budgets
- Alerting strategies

### Próximos Passos

#### **Implementações Avançadas**
1. **Kubernetes Deployment**
   - Helm charts
   - HPA (Horizontal Pod Autoscaler)
   - Service mesh (Istio)

2. **Monitoring Stack**
   - Prometheus + Grafana
   - ELK Stack
   - Jaeger tracing

3. **CI/CD Pipeline**
   - GitHub Actions
   - Blue-green deployment
   - Canary releases

#### **Padrões Empresariais**
1. **Event Sourcing**
2. **CQRS (Command Query Responsibility Segregation)**
3. **Saga Pattern**
4. **Event-driven Architecture**

---

## 🏁 Conclusão

Este repositório oferece uma **comparação prática** entre duas estratégias fundamentais de processamento assíncrono:

### **🎯 Para Aprendizado:**
- Compare implementações lado a lado
- Teste diferentes cenários de carga
- Analise trade-offs na prática
- Entenda quando usar cada abordagem

### **🚀 Para Projetos Reais:**
- Use `supervisor-horizon/` para MVPs e desenvolvimento
- Use `docker-queue-balance/` para aplicações enterprise
- Considere híbrido: dev simple, prod distributed

### **� Para Carreira:**
- Domínio de patterns fundamentais
- Experiência com Docker e containers
- Conhecimento de trade-offs arquiteturais
- Base para tecnologias avançadas (Kubernetes, etc)

**💡 Lembre-se**: Não existe "bala de prata" - cada estratégia tem seu lugar dependendo do contexto, equipe, budget e requirements.

---

**🎓 Happy Learning!** 

Para dúvidas específicas, consulte os READMEs individuais de cada projeto ou abra uma issue no repositório.

### Projeto docker-queue-balance

```bash
cd docker-queue-balance

# 1. Instalar dependências
composer install

# 2. Configurar environment
cp .env.example .env
php artisan key:generate

# 3. Executar migrations
php artisan migrate

# 4. Subir os containers (Redis + Workers de filas)
docker-compose up -d

# 5. Processar o arquivo CSV
php artisan process:csv-data --batch-size=10

# 6. Monitorar os logs dos containers
docker-compose logs -f queue-high
docker-compose logs -f queue-default  
docker-compose logs -f queue-low
```

### Projeto supervisor-horizon

```bash
cd supervisor-horizon

# 1. Subir containers (Redis + App com Supervisor)
docker-compose up -d

# 2. Inicializar projeto
./init.sh

# 3. Processar o arquivo CSV
docker exec supervisor-app php artisan process:csv-data --batch-size=10

# 4. Monitorar
# Dashboard: http://localhost:8000/horizon
# Logs: docker exec supervisor-app tail -f storage/logs/horizon.log
# Status: docker exec supervisor-app supervisorctl status
```

---

## 📊 Dados de Exemplo

O arquivo `data.csv` contém dados climáticos simulados:
- **data**: timestamp da leitura
- **temperatura**: valor da temperatura

Exemplo:
```csv
data,temperatura
2022-01-01 00:00:00,3.1
2022-01-01 00:05:00,1.7
...
```

---

## 🔍 Diferenças dos Conceitos

### Docker Queue Balance
- ✅ Controle granular por fila
- ✅ Isolamento completo por container
- ✅ Escalabilidade horizontal simples
- ✅ Workers dedicados por fila
- ❌ Configuração manual
- ❌ Sem interface gráfica

### Supervisor + Horizon
- ✅ Gerenciamento automático e inteligente
- ✅ Interface web rica com métricas
- ✅ Auto-scaling baseado em carga
- ✅ Configuração simplificada
- ✅ Docker apenas para Redis
- ❌ Dependência do Horizon
- ❌ Overhead adicional

---

## 📈 Monitoramento

### Docker Queue Balance
```bash
# Ver status dos containers
docker-compose ps

# Logs em tempo real
docker-compose logs -f

# Ver jobs nas filas (Redis)
docker-compose exec redis redis-cli
> LLEN laravel_database_queue:high-priority
> LLEN laravel_database_queue:default
> LLEN laravel_database_queue:low-priority
```

### Supervisor + Horizon
- Dashboard: `http://localhost:8000/horizon`
- Métricas em tempo real
- Jobs processados
- Falha de jobs
- Throughput por fila

---

## 🛠️ Arquitetura

### Docker Queue Balance
```
CSV → Laravel Command → Jobs → Filas Específicas → Containers Workers → Database
                                    ↓
                               [high-priority]  → Container 1
                               [default]        → Container 2  
                               [low-priority]   → Container 3
```

### Supervisor + Horizon
```
CSV → Laravel Command → Jobs → Horizon Auto-Scaler → Dynamic Workers → Database
                                   ↓
                              [Uma fila 'default']
                              [1-5 workers dinâmicos]
                              [Supervisor + Horizon em container]
```

