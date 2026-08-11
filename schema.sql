SHOW CREATE TABLE rendez_vous; SELECT id, statut, HEX(statut) AS hex_statut FROM rendez_vous WHERE statut NOT IN ('Planifié','Réalisé','Annulé','Décalé') ORDER BY id LIMIT 20;
