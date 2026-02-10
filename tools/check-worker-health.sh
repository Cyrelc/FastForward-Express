#!/usr/bin/env bash
#
# Laravel Queue Worker Health Check & Auto-Recovery Script
# Monitors supervisor workers, checks queue processing, and auto-restarts on failure
#

set -e

# Configuration
PROJECT_ROOT="${PROJECT_ROOT:-/var/www/FastForward-Express}"
DISCORD_WEBHOOK_URL="${DISCORD_WEBHOOK_URL:-}"
MAX_QUEUE_AGE_SECONDS=300  # 5 minutes - if oldest job is older than this, workers might be stuck
CONSECUTIVE_FAILURES_THRESHOLD=2
WORKER_GROUP="${LARAVEL_WORKER_GROUP:-laravel-queue}"  # Use env var or default

# Helper: Send Discord notification
send_discord_alert() {
    local message="$1"
    local severity="${2:-warning}"  # warning, error, success
    
    if [[ -z "$DISCORD_WEBHOOK_URL" ]]; then
        return 0
    fi
    
    local color
    case "$severity" in
        error)   color=15158332 ;;  # Red
        warning) color=16776960 ;;  # Yellow
        success) color=3066993 ;;   # Green
        *)       color=9807270 ;;   # Gray
    esac
    
    local payload=$(cat <<EOF
{
  "embeds": [{
    "title": "🔧 Worker Health Monitor",
    "description": "$message",
    "color": $color,
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%S.000Z)",
    "footer": {"text": "$(hostname)"}
  }]
}
EOF
)
    
    curl -sS -X POST -H "Content-Type: application/json" \
        -d "$payload" "$DISCORD_WEBHOOK_URL" &>/dev/null || true
}

# Helper: Log to Laravel activity log via artisan command
log_to_laravel() {
    local message="$1"
    local status="$2"
    
    cd "$PROJECT_ROOT"
    sudo -u www-data php artisan worker:health-store --status="$status" --message="$message" 2>/dev/null || true
}

# Check 1: Supervisor process status
check_supervisor_workers() {
    local status
    status=$(sudo supervisorctl status ${WORKER_GROUP}: 2>/dev/null || echo "FATAL")
    
    if echo "$status" | grep -qE "RUNNING|STARTING"; then
        return 0  # Workers are running
    else
        return 1  # Workers are down
    fi
}

# Check 2: Queue age - verify workers are actually processing
check_queue_processing() {
    cd "$PROJECT_ROOT"
    
    local oldest_job_age
    oldest_job_age=$(sudo -u www-data php artisan tinker --execute="
        \$oldest = DB::table('jobs')->orderBy('created_at', 'asc')->first();
        if (\$oldest) {
            echo time() - \$oldest->created_at;
        } else {
            echo 0;
        }
    " 2>/dev/null | tail -n1 | tr -d '\n')
    
    if [[ -z "$oldest_job_age" ]] || [[ "$oldest_job_age" -lt "$MAX_QUEUE_AGE_SECONDS" ]]; then
        return 0  # Queue is being processed
    else
        return 1  # Queue appears stuck
    fi
}

# Action: Restart workers
restart_workers() {
    local reason="$1"
    
    echo "Restarting workers: $reason"
    
    sudo supervisorctl restart ${WORKER_GROUP}: 2>&1 || {
        echo "ERROR: Failed to restart workers via supervisor"
        send_discord_alert "❌ **Failed to restart workers**\nReason: $reason\nAction required!" "error"
        log_to_laravel "Failed to restart workers: $reason" "error"
        return 1
    }
    
    # Wait for workers to stabilize
    sleep 5
    
    send_discord_alert "✅ **Workers restarted successfully**\nReason: $reason" "success"
    log_to_laravel "Workers restarted: $reason" "recovered"
    
    return 0
}

# Main health check logic
main() {
    local supervisor_ok=false
    local queue_ok=false
    local overall_status="healthy"
    local failure_reason=""
    
    # Run checks
    if check_supervisor_workers; then
        supervisor_ok=true
    else
        supervisor_ok=false
        failure_reason="Supervisor workers are not running"
    fi
    
    if check_queue_processing; then
        queue_ok=true
    else
        queue_ok=false
        if [[ -n "$failure_reason" ]]; then
            failure_reason="$failure_reason; Queue processing appears stuck"
        else
            failure_reason="Queue processing appears stuck (oldest job > ${MAX_QUEUE_AGE_SECONDS}s)"
        fi
    fi
    
    # Determine overall health
    if [[ "$supervisor_ok" == true ]] && [[ "$queue_ok" == true ]]; then
        overall_status="healthy"
        log_to_laravel "Workers healthy" "up"
        echo "✓ Workers healthy"
        exit 0
    else
        overall_status="unhealthy"
        
        # Track consecutive failures
        local failure_count_file="/tmp/laravel-worker-failure-count"
        local current_count=0
        
        if [[ -f "$failure_count_file" ]]; then
            current_count=$(cat "$failure_count_file")
        fi
        current_count=$((current_count + 1))
        echo "$current_count" > "$failure_count_file"
        
        echo "✗ Workers unhealthy (failure #${current_count}): $failure_reason"
        log_to_laravel "Workers unhealthy: $failure_reason" "down"
        
        # Only restart after consecutive failures threshold
        if [[ $current_count -ge $CONSECUTIVE_FAILURES_THRESHOLD ]]; then
            echo "Threshold reached ($CONSECUTIVE_FAILURES_THRESHOLD failures). Attempting restart..."
            
            if restart_workers "$failure_reason"; then
                # Reset failure counter on successful restart
                rm -f "$failure_count_file"
                exit 0
            else
                exit 1
            fi
        else
            echo "Waiting for next check (${current_count}/${CONSECUTIVE_FAILURES_THRESHOLD} failures)"
            exit 1
        fi
    fi
}

main "$@"
