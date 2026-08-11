<?php

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=contact_filementcrm;charset=utf8mb4', 'filementcrm', 'filementcrm', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sqls = [
    "UPDATE rendez_vous SET statut = 'Planifié' WHERE HEX(statut) = '506C616E6966693F3F'",
    "ALTER TABLE `rendez_vous` ALTER COLUMN `statut` SET DEFAULT 'Planifié'",
    "SELECT id, statut, HEX(statut) AS hex_statut FROM rendez_vous WHERE statut NOT IN ('Planifié', 'Réalisé', 'Annulé', 'Décalé') ORDER BY id",
    "SELECT COLUMN_DEFAULT, HEX(COLUMN_DEFAULT) AS hex_default FROM information_schema.columns WHERE table_schema = 'contact_filementcrm' AND table_name = 'rendez_vous' AND column_name = 'statut'",
];

foreach ($sqls as $sql) {
    $stmt = $pdo->query($sql);
    if ($stmt instanceof PDOStatement) {
        $rows = $stmt->fetchAll();
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                echo implode(' | ', $row) . "\n";
            }
        }
    }
}
