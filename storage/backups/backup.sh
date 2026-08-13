#!/bin/bash
# Backup quotidien de contact_filementcrm

DB_NAME="contact_filementcrm"
DB_USER="contact_filacrm"
DB_PASS="CrmPass2024"
BACKUP_DIR="/home/mbl/backups/database"
RETENTION_DAYS=30
LOG_FILE="$BACKUP_DIR/backup.log"

mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_backup_${TIMESTAMP}.sql.gz"

{
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] === Démarrage du backup ==="
    
    if mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null | gzip > "$BACKUP_FILE"; then
        SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        echo "[$(date +'%Y-%m-%d %H:%M:%S')] ✓ Backup réussi: $BACKUP_FILE ($SIZE)"
    else
        echo "[$(date +'%Y-%m-%d %H:%M:%S')] ✗ ERREUR: Backup échoué"
        exit 1
    fi
    
    # Nettoyer les anciens backups
    find "$BACKUP_DIR" -name "${DB_NAME}_backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete
    CLEANED=$(find "$BACKUP_DIR" -name "${DB_NAME}_backup_*.sql.gz" | wc -l)
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backups conservés: $CLEANED fichiers"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] === Backup terminé ==="
} >> "$LOG_FILE" 2>&1

exit 0
