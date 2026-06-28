# Utilisateurs de Démo - Accès et Credentials

## Overview
Ce document contient les identifiants de connexion pour les utilisateurs de démo créés par `DemoUsersSeeder`.

## Rôles et Utilisateurs

### Super Admin (super_admin)
- **Email:** `jean.martin.1@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `marie.bernard.2@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `pierre.dubois.3@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:** Accès complet à tous les panels et fonctionnalités système.

---

### Administrateur (administrateur)
- **Email:** `sophie.thomas.4@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `luc.robert.5@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `claire.richard.6@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:** Accès complet au panel NS Conseil, gestion des utilisateurs et configuration.

---

### Commercial (commercial)
- **Email:** `thomas.petit.7@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `emma.durand.8@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `nicolas.leroy.9@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:** 
- Panel NS Conseil
- Gestion des prospects et partenaires (CRUD)
- Gestion des rendez-vous
- Création d'opportunités

---

### Téléprospecteur (teleprospecteur)
- **Email:** `julie.moreau.10@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `antoine.simon.11@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `camille.laurent.12@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:**
- Panel NS Conseil
- Gestion des prospects (CRUD)
- Enregistrement des appels
- Workflow phoning CSE

---

### Opérateur N1 (operateur_n1)
- **Email:** `maxime.lefebvre.13@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `sarah.michel.14@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `hugo.garcia.15@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:**
- Panel Allopro
- Lecture seule sur la plupart des entités
- Enregistrement des appels

---

### Back-Office (back_office)
- **Email:** `léa.david.16@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `louis.bertrand.17@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `manon.roux.18@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:**
- Panel Allopro
- Gestion administrative
- Support clients

---

### Responsable Plateau (responsable_plateau)
- **Email:** `alexandre.fournier.19@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `chloé.guérin.20@demo-crm.local` | **Mot de passe:** `demo123`
- **Email:** `mathieu.garcia.21@demo-crm.local` | **Mot de passe:** `demo123`

**Accès:**
- Panel Allopro
- Supervision de l'équipe
- Rapports et statistiques
- Gestion des tickets

---

## Instructions d'utilisation

### Lancer le seeder
```bash
php artisan db:seed --class=DemoUsersSeeder
```

### Réinitialiser avec les utilisateurs de démo
```bash
php artisan migrate:fresh
php artisan db:seed --class=DemoUsersSeeder
```

### Notes importantes
- Tous les utilisateurs ont le même mot de passe: `demo123`
- Les emails sont formatés: `{prenom}.{nom}.{numero}@demo-crm.local`
- Les secteurs sont assignés aléatoirement pour les commerciaux et téléprospecteurs
- Les utilisateurs sont créés avec `actif = true`

## Matrice des permissions par rôle

| Entité | Super Admin | Admin | Commercial | Téléprospecteur | Opérateur N1 | Back-Office | Resp. Plateau |
|--------|-------------|-------|------------|-----------------|--------------|-------------|---------------|
| **Prospects** |
| View | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Edit | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| Delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Partenaires** |
| View | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Appels** |
| View | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Rendez-vous** |
| View | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ |
| Create | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Opportunités** |
| View | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Create | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

## Panels par rôle

| Rôle | NS Conseil | Admin | Super Admin | Allopro |
|------|------------|-------|-------------|---------|
| Super Admin | ✅ | ✅ | ✅ | ✅ |
| Administrateur | ✅ | ✅ | ❌ | ❌ |
| Commercial | ✅ | ❌ | ❌ | ❌ |
| Téléprospecteur | ✅ | ❌ | ❌ | ❌ |
| Opérateur N1 | ❌ | ❌ | ❌ | ✅ |
| Back-Office | ❌ | ❌ | ❌ | ✅ |
| Responsable Plateau | ❌ | ❌ | ❌ | ✅ |
