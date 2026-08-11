SET NAMES utf8mb4; SELECT id, statut, HEX(statut) AS hex_statut, CHAR_LENGTH(statut) AS char_len, LENGTH(statut) AS byte_len FROM rendez_vous ORDER BY id;
