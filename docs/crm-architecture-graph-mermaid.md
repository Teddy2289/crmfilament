# Architecture CRM - Diagramme Mermaid

## Graph des Relations Principales

```mermaid
erDiagram
    User ||--o{ Prospect : "teleprospecteur_id"
    User ||--o{ Prospect : "commercial_id"
    User ||--o{ Prospect : "valide_par"
    User ||--o{ Partenaire : "commercial_id"
    User ||--o{ RendezVous : "commercial_id"
    User ||--o{ RendezVous : "teleprospecteur_id"
    User ||--o{ Appel : "user_id"
    User }|--|| GroupeTelepro : "belongsToMany"
    
    Prospect ||--|| CampagnePhoning : "campagne_id"
    Prospect ||--o| Partenaire : "converti_partenaire_id"
    Prospect ||--o{ Appel : "morphMany"
    Prospect ||--o{ RendezVous : "morphMany"
    Prospect ||--o{ Document : "morphMany"
    Prospect ||--o{ Opportunite : "hasOne"
    
    Partenaire ||--o{ Client : "hasMany"
    Partenaire ||--o{ ContactPartenaire : "hasMany"
    Partenaire ||--o| AdresseCse : "hasOne"
    Partenaire ||--o| Tarification : "hasOne"
    Partenaire ||--o| ActiviteVente : "hasOne"
    Partenaire ||--o| ActivitePermanence : "hasOne"
    Partenaire ||--o| RemboursementEmployeur : "hasOne"
    Partenaire ||--o{ HistoriqueConseiller : "hasMany"
    Partenaire ||--o{ AutresInterlocuteurs : "hasMany"
    Partenaire ||--o{ Appel : "morphMany"
    Partenaire ||--o{ RendezVous : "morphMany"
    Partenaire ||--o{ Document : "morphMany"
    Partenaire ||--|| EntiteCommerciale : "entite_id"
    Partenaire ||--o| Partenaire : "entreprise_mere_id"
    Partenaire ||--o| Partenaire : "parrain_partenaire_id"
    Partenaire ||--o| Consultant : "conseiller_id"
    Partenaire ||--o| Prospect : "prospect_id"
    
    Client ||--o{ Proposition : "ref_client"
    Client ||--o{ DossierFormation : "personne_id"
    Client ||--o| Partenaire : "partenaire_id"
    Client ||--o| Parrain : "parrain_id"
    Client ||--o| User : "commercial_id"
    Client ||--o{ RendezVous : "morphMany"
    Client ||--o{ Document : "morphMany"
    Client }|--|| Partenaire : "belongsToMany"
    
    Proposition }|--|| Client : "ref_client"
    
    DossierFormation ||--o| Client : "personne_id"
    DossierFormation ||--o| EntiteCommerciale : "entite_id"
    DossierFormation ||--o| Consultant : "consultant_accueil_id"
    DossierFormation ||--o| Consultant : "consultant_formateur_id"
    DossierFormation ||--o| HeuresFormation : "hasOne"
    DossierFormation ||--o| PlanningFormation : "hasOne"
    
    EntiteCommerciale ||--o{ Consultant : "hasMany"
    EntiteCommerciale ||--o{ CampagnePhoning : "hasMany"
    EntiteCommerciale ||--o{ Partenaire : "hasMany"
    EntiteCommerciale ||--o{ DossierFormation : "hasMany"
    
    CampagnePhoning ||--o| EntiteCommerciale : "entite_id"
    CampagnePhoning ||--o| GroupeTelepro : "groupe_telepro_id"
    CampagnePhoning ||--o| User : "user_id"
    CampagnePhoning ||--o{ Prospect : "hasMany"
    
    GroupeTelepro }|--|| User : "belongsToMany"
    GroupeTelepro ||--o{ CampagnePhoning : "hasMany"
    
    Consultant ||--o{ Partenaire : "conseiller_id"
    Consultant ||--o| EntiteCommerciale : "entite_id"
    Consultant ||--o| DossierFormation : "consultant_accueil_id"
    Consultant ||--o| DossierFormation : "consultant_formateur_id"
```

## Workflow Prospection → Partenariat

```mermaid
flowchart TD
    A[Prospect: AC<br/>À contacter] --> B[STD_NR / CSE_NR<br/>Standard/CSE non référencé]
    B --> C[STD_Joint<br/>Standard joint]
    C --> D{Réponse ?}
    D -->|Oui| E[RP / RPC<br/>Réponse positive]
    D -->|Non| F[KO<br/>Refus]
    E --> G[QF<br/>Qualifié]
    G --> H{Validé ?}
    H -->|Oui| I[convertirEnPartenaire]
    H -->|Non| G
    I --> J[Partenaire<br/>Signé accord cadre]
    J --> K[ContactPartenaire<br/>Migration contacts]
    K --> L[Partenaire actif]
    
    style A fill:#e1f5ff
    style G fill:#fff4e1
    style J fill:#e8f5e9
    style F fill:#ffebee
```

## Workflow Partenariat

```mermaid
flowchart TD
    A[À prospecter] --> B[En cours prospection]
    B --> C[RDV en cours]
    C --> D{Accord ?}
    D -->|Oui| E[Signé accord cadre]
    D -->|Non| F[Refus]
    E --> G[Convention engagement]
    G --> H[Partenaire conventionné]
    
    style A fill:#e1f5ff
    style E fill:#fff4e1
    style G fill:#e8f5e9
    style F fill:#ffebee
```

## Workflow Client Formation

```mermaid
flowchart TD
    A[Client: prospect] --> B[en_cours]
    B --> C{Formation ?}
    C -->|En cours| B
    C -->|Terminée| D[termine]
    C -->|Certifiée| E[certifie]
    C -->|Abandon| F[abandonne]
    
    D --> G[Proposition: Terminée]
    E --> H[DossierFormation: valide]
    F --> I[Proposition: Annulée]
    
    style A fill:#e1f5ff
    style E fill:#e8f5e9
    style F fill:#ffebee
```

## Flux de Données Complet

```mermaid
flowchart LR
    subgraph Prospection
        P1[Prospect] --> P2[Appel]
        P1 --> P3[RendezVous]
        P1 --> P4[Document]
    end
    
    subgraph Conversion
        P1 -->|QF validé| PA1[Partenaire]
    end
    
    subgraph Partenariat
        PA1 --> PA2[ContactPartenaire]
        PA1 --> PA3[ActiviteVente]
        PA1 --> PA4[ActivitePermanence]
    end
    
    subgraph Client
        PA1 --> C1[Client]
        C1 --> C2[Proposition]
        C1 --> C3[DossierFormation]
    end
    
    subgraph Formation
        C3 --> F1[HeuresFormation]
        C3 --> F2[PlanningFormation]
    end
    
    subgraph Users
        U1[User] --> P1
        U1 --> PA1
        U1 --> C1
    end
    
    style P1 fill:#e1f5ff
    style PA1 fill:#fff4e1
    style C1 fill:#e8f5e9
    style U1 fill:#f3e5f5
```

## Relations Morphiques

```mermaid
graph TD
    subgraph Appels
        A[Appel] --> A1[Prospect]
        A --> A2[Partenaire]
    end
    
    subgraph RendezVous
        R[RendezVous] --> R1[Prospect]
        R --> R2[Partenaire]
        R --> R3[Client]
    end
    
    subgraph Documents
        D[Document] --> D1[Prospect]
        D --> D2[Partenaire]
        D --> D3[Client]
    end
    
    subgraph Historique
        H[HistoriqueInteractionUser] --> H1[Prospect]
        H --> H2[Partenaire]
    end
    
    style A fill:#e1f5ff
    style R fill:#fff4e1
    style D fill:#e8f5e9
    style H fill:#f3e5f5
```

## Structure Organisationnelle

```mermaid
graph TB
    subgraph Entités
        EC[EntiteCommerciale]
    end
    
    subgraph Partenaires
        PM[Partenaire Mère]
        PF1[Filiale 1]
        PF2[Filiale 2]
    end
    
    subgraph Clients
        C1[Client 1]
        C2[Client 2]
        C3[Client 3]
    end
    
    subgraph Groupes
        GT[GroupeTelepro]
        U1[User 1]
        U2[User 2]
        U3[User 3]
    end
    
    EC --> PM
    EC --> PF1
    EC --> PF2
    
    PM --> PF1
    PM --> PF2
    
    PM --> C1
    PM --> C2
    PF1 --> C3
    
    GT --> U1
    GT --> U2
    GT --> U3
    
    style EC fill:#e1f5ff
    style PM fill:#fff4e1
    style GT fill:#e8f5e9
```

## Cycle de Vie Complet

```mermaid
stateDiagram-v2
    [*] --> Prospect: Création
    Prospect --> EnQualification: Premier contact
    EnQualification --> Qualifie: QF validé
    EnQualification --> KO: Refus
    Qualifie --> Partenaire: Conversion
    Partenaire --> Conventionné: Signature accord
    Conventionné --> Client: Acquisition client
    Client --> EnFormation: Proposition active
    EnFormation --> Formé: Formation terminée
    Formé --> Certifié: Certification obtenue
    KO --> [*]
    Certifié --> [*]
    
    note right of Prospect
        AC → STD_Joint → RP/RPC → QF
    end note
    
    note right of Partenaire
        À prospecter → RDV en cours → 
        Signé accord cadre → Convention engagement
    end note
    
    note right of Client
        prospect → en_cours → termine → certifie
    end note
```

## KPIs et Métriques

```mermaid
graph LR
    subgraph Prospection
        P1[Total Prospects]
        P2[Taux Qualification]
        P3[Taux KO]
        P4[Rappels en retard]
    end
    
    subgraph Partenariat
        PA1[Total Partenaires]
        PA2[Partenaires Actifs]
        PA3[Conventionnés]
        PA4[À relancer]
    end
    
    subgraph Client
        C1[Total Clients]
        C2[Contactables]
        C3[Avec Propositions]
        C4[Avec CPF]
    end
    
    subgraph Formation
        F1[Heures Formées]
        F2[Heures Restantes]
        F3[Taux Completion]
        F4[Formations en retard]
    end
    
    style P1 fill:#e1f5ff
    style PA1 fill:#fff4e1
    style C1 fill:#e8f5e9
    style F1 fill:#f3e5f5
```

## Intégrations Systèmes

```mermaid
graph TB
    subgraph CRM
        CRM[CRM Filament]
        U[Users]
        P[Prospects]
        PA[Partenaires]
        C[Clients]
    end
    
    subgraph Google
        G[Google API]
        GT[Google Tokens]
    end
    
    subgraph Ringover
        R[Ringover API]
        A[Appels]
    end
    
    U --> G
    U --> GT
    U --> R
    P --> A
    PA --> A
    
    CRM --> G
    CRM --> R
    
    style CRM fill:#e1f5ff
    style G fill:#fff4e1
    style R fill:#e8f5e9
```

## Permissions et Rôles

```mermaid
graph TB
    subgraph Rôles
        SA[Super Admin]
        A[Admin]
        C[Commercial]
        T[Téléprospecteur]
        O[Opérateur N1]
        BO[Back Office]
        S[Responsable Plateau]
    end
    
    subgraph Permissions
        P1[Gestion Users]
        P2[Prospection]
        P3[Gestion Partenaires]
        P4[Gestion Clients]
        P5[Support]
        P6[Supervision]
    end
    
    SA --> P1
    SA --> P2
    SA --> P3
    SA --> P4
    SA --> P5
    SA --> P6
    
    A --> P2
    A --> P3
    A --> P4
    
    C --> P2
    C --> P3
    C --> P4
    
    T --> P2
    
    O --> P5
    
    BO --> P4
    BO --> P5
    
    S --> P2
    S --> P6
    
    style SA fill:#ffebee
    style A fill:#fff4e1
    style C fill:#e8f5e9
    style T fill:#e1f5ff
