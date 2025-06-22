#!/bin/bash

# Script para monitorar workers em tempo real
echo "🚀 Iniciando monitoramento dos workers..."
echo "Pressione Ctrl+C para sair"
echo "================================="

# Função para mostrar estatísticas das filas
show_queue_stats() {
    echo "📊 Estatísticas das Filas - $(date '+%H:%M:%S')"
    echo "----------------------------------------"
    
    # Verifica jobs pendentes no Redis
    docker-compose exec redis redis-cli llen "queues:high-priority" | sed 's/^/  Alta Prioridade: /'
    docker-compose exec redis redis-cli llen "queues:default" | sed 's/^/  Padrão: /'
    docker-compose exec redis redis-cli llen "queues:low-priority" | sed 's/^/  Baixa Prioridade: /'
    
    echo ""
}

# Função para mostrar status dos containers
show_container_status() {
    echo "🐳 Status dos Containers"
    echo "------------------------"
    docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "(queue-|laravel-)" | head -10
    echo ""
}

# Loop principal
while true; do
    clear
    echo "🔄 Monitor de Workers Laravel - $(date)"
    echo "========================================"
    echo ""
    
    show_container_status
    show_queue_stats
    
    echo "📝 Últimas atividades dos workers:"
    echo "-----------------------------------"
    
    # Mostra últimas linhas dos logs de cada worker
    echo "🔥 HIGH PRIORITY:"
    docker logs docker-queue-balance-queue-high-1 --tail=2 2>/dev/null | tail -1 || echo "  Sem atividade"
    
    echo "⚡ DEFAULT:"
    docker logs docker-queue-balance-queue-default-1 --tail=2 2>/dev/null | tail -1 || echo "  Sem atividade"
    
    echo "🐌 LOW PRIORITY:"
    docker logs queue-low-worker --tail=2 2>/dev/null | tail -1 || echo "  Sem atividade"
    
    echo ""
    echo "Atualização automática em 3 segundos..."
    sleep 3
done
