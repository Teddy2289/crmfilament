<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=contact_filementcrm;charset=utf8mb4', 'filementcrm', 'filementcrm', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function dump($title, $rows) {
    echo "--- $title ---\n";
    foreach ($rows as $row) {
        echo implode(' | ', $row) . "\n";
    }
}

$invalid = $pdo->query("SELECT id, statut, HEX(statut) AS hex_statut, CHAR_LENGTH(statut) AS char_len, LENGTH(statut) AS byte_len FROM rendez_vous WHERE statut NOT IN ('Planifié','Réalisé','Annulé','Décalé') ORDER BY id")->fetchAll();
dump('invalid before', $invalid);

$update = $pdo->prepare("UPDATE rendez_vous SET statut = 'Planifié' WHERE HEX(statut) = ?");
$hexes = array_unique(array_map(fn($row) => $row['hex_statut'], $invalid));
foreach ($hexes as $hex) {
    $update->execute([$hex]);
}

$pdo->exec("ALTER TABLE `rendez_vous` MODIFY `statut` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Planifié';");

$invalidAfter = $pdo->query("SELECT id, statut, HEX(statut) AS hex_statut, CHAR_LENGTH(statut) AS char_len, LENGTH(statut) AS byte_len FROM rendez_vous WHERE statut NOT IN ('Planifié','Réalisé','Annulé','Décalé') ORDER BY id")->fetchAll();
dump('invalid after', $invalidAfter);

$default = $pdo->query("SELECT COLUMN_DEFAULT, HEX(COLUMN_DEFAULT) AS hex_default FROM information_schema.columns WHERE table_schema='contact_filementcrm' AND table_name='rendez_vous' AND column_name='statut'")->fetchAll();
dump('column default', $default);
