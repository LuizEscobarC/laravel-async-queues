# Laravel Supervisor + Horizon Queue Management

Este projeto demonstra ## 🐳 Infraestrutura Docker

Agora **tudo está containerizado**:
- **Redis**: Broker de mensagens para as filas
- **Aplicação Laravel**: Container com PHP, Supervisor e Horizon
- **Supervisor**: Gerencia o processo Horizon dentro do container
- **Horizon**: Interface web e gerenciador de filasciamento de filas assíncronas usando **Supervisor** para manter o **Laravel Horizon** rodando.

## 🎯 Conceito

- **Supervisor**: Gerenciador de processos que mantém o Horizon sempre rodando
- **Laravel Horizon**: Gerencia e balanceia automaticamente as filas Redis
- **Balanceamento Automático**: Horizon distribui jobs entre múltiplas filas

## 🏗️ Arquitetura

```
CSV Data → Laravel Command → Jobs → Horizon Auto-Balancer → Workers → Database
                                       ↓
                                [default queue]
                                       ↓
                              [Horizon distribui carga]
                                       ↓
                           [1-5 workers dinâmicos baseado na carga]
                                       ↑
                              [Supervisor mantém Horizon ativo]
                                       ↑
                              [Tudo dentro do container Docker]
```

## 🚀 Como usar

### 1. Subir os containers
```bash
docker-compose up -d
```

### 2. Inicializar o projeto
```bash
./init.sh
```

### 3. Processar dados CSV
```bash
docker exec supervisor-app php artisan process:csv-data --batch-size=15
```

### 4. Monitorar no Dashboard
Acesse: `http://localhost:8000/horizon`

### 5. Ver logs do Horizon
```bash
docker exec supervisor-app tail -f storage/logs/horizon.log
```

### 6. Monitorar Supervisor
```bash
docker exec supervisor-app supervisorctl status
```

## 📊 Configuração do Horizon

| Configuração | Valor | Descrição |
|--------------|-------|-----------|
| **Queue** | `default` | Fila única gerenciada pelo Horizon |
| **Max Workers** | 1-5 | Auto-scaling baseado na carga |
| **Balance Strategy** | `auto` | Horizon balanceia automaticamente |
| **Scaling Strategy** | `time` | Escala baseado no tempo de espera |

## 🎛️ Dashboard Horizon

O Horizon oferece:
- ✅ Monitoramento em tempo real
- ✅ Métricas de throughput
- ✅ Jobs falhados
- ✅ Tempo de processamento
- ✅ Controle de workers

## � Infraestrutura Docker

Diferente do projeto docker-queue-balance, aqui o Docker é usado apenas para o **Redis**:
- **Redis**: Broker de mensagens para as filas
- **Aplicação Laravel**: Roda diretamente no host
- **Horizon**: Roda diretamente no host
- **Supervisor**: Gerencia o processo Horizon

## 🔧 Configuração do Supervisor

O arquivo `horizon.conf` configura:
- **Comando**: `php artisan horizon`
- **Auto-restart**: Sim
- **Logs**: `storage/logs/horizon.log`
- **Timeout**: 3600s para shutdown graceful

## 💡 Vantagens desta Abordagem

- **Auto-Scaling**: Horizon ajusta workers automaticamente baseado na carga
- **Visual**: Dashboard rico para monitoramento em tempo real
- **Resiliente**: Supervisor garante que Horizon nunca pare
- **Inteligente**: Balanceamento baseado em métricas de performance
- **Métricas**: Insights detalhados de throughput e latência
- **Simplicidade**: Uma fila, gerenciamento automático
