# Diagrammes du Système de Blocage/Déblocage avec Archivage Neon

## 🔄 Flux 1: Blocage Immédiat (date = aujourd'hui)

```
Admin                API                CompteService           CompteArchiveService    PostgreSQL       Neon
  |                   |                       |                         |                  |            |
  |-- POST /bloquer ->|                       |                         |                  |            |
  |  (date=today)     |                       |                         |                  |            |
  |                   |-- bloquerCompte() --->|                         |                  |            |
  |                   |                       |-- find(compte_id) ----->|                  |            |
  |                   |                       |<----- compte ------------|                  |            |
  |                   |                       |                         |                  |            |
  |                   |                       |-- Valider type épargne  |                  |            |
  |                   |                       |-- Valider statut actif  |                  |            |
  |                   |                       |                         |                  |            |
  |                   |                       |-- update(statut=bloqué)-|->                |            |
  |                   |                       |                         |  UPDATE comptes |            |
  |                   |                       |                         |<-                |            |
  |                   |                       |                         |                  |            |
  |                   |                       |-- archiveCompte() ----->|                  |            |
  |                   |                       |                         |-- INSERT ------->|            |
  |                   |                       |                         |   comptes_archives            |
  |                   |                       |                         |<-----------------|            |
  |                   |                       |                         |                  |            |
  |                   |                       |-- delete(soft) -------->|                  |            |
  |                   |                       |                         |  UPDATE deleted_at            |
  |                   |                       |                         |<-                |            |
  |                   |                       |                         |                  |            |
  |                   |<-- Success: Archivé --|                         |                  |            |
  |<-- 200: Archivé --|                       |                         |                  |            |
  | dans Neon         |                       |                         |                  |            |
```

## 🕒 Flux 2: Blocage Programmé (date future)

```
Admin                API                CompteService           PostgreSQL
  |                   |                       |                    |
  |-- POST /bloquer ->|                       |                    |
  |  (date=future)    |                       |                    |
  |                   |-- bloquerCompte() --->|                    |
  |                   |                       |-- find(compte_id) ->|
  |                   |                       |<----- compte -------|
  |                   |                       |                    |
  |                   |                       |-- Valider type     |
  |                   |                       |-- Valider statut   |
  |                   |                       |                    |
  |                   |                       |-- update(         ->|
  |                   |                       |    statut=actif,   |
  |                   |                       |    blocage_        |
  |                   |                       |    programme=true  |
  |                   |                       |   )                |
  |                   |                       |<-------------------|
  |                   |                       |                    |
  |                   |<-- Success: Sera -----|                    |
  |<-- 200: Sera      |    bloqué le XX/XX    |                    |
  | bloqué le XX/XX   |                       |                    |
```

## ⏰ Flux 3: Blocage Automatique (Job quotidien)

```
Scheduler         BloquerComptesEpargneJob    Compte Model     CompteArchiveService    PostgreSQL    Neon
    |                        |                      |                 |                   |           |
    |-- Minuit ------------->|                      |                 |                   |           |
    |                        |-- Query:             |                 |                   |           |
    |                        |   blocage_programme=true                |                   |           |
    |                        |   dateDebutBlocage<=today               |                   |           |
    |                        |                      |<----------------|                   |           |
    |                        |<-- [comptes] --------|                 |                   |           |
    |                        |                      |                 |                   |           |
    |                        |-- foreach compte:    |                 |                   |           |
    |                        |                      |                 |                   |           |
    |                        |-- update(           ->|                 |                   |           |
    |                        |    statut=bloqué    |  UPDATE comptes |                   |           |
    |                        |   )                 |<-                |                   |           |
    |                        |                      |                 |                   |           |
    |                        |-- archiveCompte() -->|                 |                   |           |
    |                        |                      |-- INSERT ------>|                   |           |
    |                        |                      |   comptes_archives                   |           |
    |                        |                      |<----------------|                   |           |
    |                        |                      |                 |                   |           |
    |                        |-- delete(soft) ----->|                 |                   |           |
    |                        |                      |  UPDATE deleted_at                   |           |
    |                        |                      |<-                |                   |           |
    |                        |                      |                 |                   |           |
    |-- Log: X comptes       |                      |                 |                   |           |
    |   bloqués ------------>|                      |                 |                   |           |
```

## 🔓 Flux 4: Déblocage Automatique (Job quotidien)

```
Scheduler         DebloquerComptesJob      CompteArchive    Compte Model    PostgreSQL    Neon
    |                     |                      |               |              |          |
    |-- Minuit ---------->|                      |               |              |          |
    |                     |-- Query Neon:        |               |              |          |
    |                     |   statut=bloqué      |               |              |          |
    |                     |   dateFinBlocage<=today              |              |          |
    |                     |                      |<--------------|              |          |
    |                     |<-- [archives] -------|              |              |          |
    |                     |                      |               |              |          |
    |                     |-- foreach archive:   |               |              |          |
    |                     |                      |               |              |          |
    |                     |-- find compte ------>|               |              |          |
    |                     |   (withTrashed)      |-- SELECT ---->|              |          |
    |                     |                      |<-- compte ----|              |          |
    |                     |                      |               |              |          |
    |                     |-- restore() -------->|               |              |          |
    |                     |                      |-- UPDATE ----->|              |          |
    |                     |                      |   deleted_at=null             |          |
    |                     |                      |<--------------|              |          |
    |                     |                      |               |              |          |
    |                     |-- update(           ->|               |              |          |
    |                     |    statut=actif,    |-- UPDATE ----->|              |          |
    |                     |    champs à null    |                |              |          |
    |                     |   )                 |<---------------|              |          |
    |                     |                      |               |              |          |
    |                     |-- delete archive --->|               |              |          |
    |                     |                      |-- DELETE ----->|              |          |
    |                     |                      |                |              |          |
    |-- Log: X comptes    |                      |               |              |          |
    |   débloqués ------->|                      |               |              |          |
```

## 🔍 Flux 5: Recherche d'un compte par ID

```
Client/Admin         API           CompteService        PostgreSQL        Neon
     |                |                  |                  |              |
     |-- GET /comptes/{id}               |                  |              |
     |                |                  |                  |              |
     |                |-- getById() ---->|                  |              |
     |                |                  |-- SELECT ------->|              |
     |                |                  |<-- compte -------|              |
     |                |                  |                  |              |
     |                |                  |-- Compte trouvé?|              |
     |                |                  |    OUI           |              |
     |                |<-- Compte -------|                  |              |
     |<-- 200: Compte |                  |                  |              |
     |                |                  |                  |              |
     |                                   |                  |              |
     |--- Sinon: Compte supprimé/archivé |                  |              |
     |                |                  |                  |              |
     |                |                  |-- SELECT --------|------------->|
     |                |                  |<-- archive ------|--------------|
     |                |                  |                  |              |
     |                |<-- Compte -------|                  |              |
     |                |   (depuis Neon)  |                  |              |
     |<-- 200: Compte |                  |                  |              |
     |   archivé      |                  |                  |              |
```

## 📊 États du compte

```
┌─────────────────────────────────────────────────────────────────┐
│                         CYCLE DE VIE                            │
└─────────────────────────────────────────────────────────────────┘

    ┌─────────────┐
    │   CRÉATION  │
    │  (nouveau)  │
    └──────┬──────┘
           │
           ▼
    ┌─────────────┐
    │    ACTIF    │◄──────────────────┐
    │ (PostgreSQL)│                   │
    └──────┬──────┘                   │
           │                           │
           │ POST /bloquer             │
           │ (date future)             │
           ▼                           │
    ┌─────────────┐                   │
    │    ACTIF    │                   │
    │  (blocage   │                   │
    │  programmé) │                   │
    └──────┬──────┘                   │
           │                           │
           │ Job quotidien             │
           │ (date arrivée)            │
           ▼                           │
    ┌─────────────┐                   │
    │   BLOQUÉ    │                   │
    │   (Neon)    │                   │
    └──────┬──────┘                   │
           │                           │
           │ Job quotidien             │
           │ (dateFinBlocage)          │
           │                           │
           └───────────────────────────┘
              RESTAURATION
```

## 🗄️ Répartition des données

```
┌─────────────────────────────────┐        ┌─────────────────────────────────┐
│       PostgreSQL (Render)       │        │          Neon (Cloud)           │
│                                 │        │                                 │
│  ┌───────────────────────────┐  │        │  ┌───────────────────────────┐  │
│  │  Comptes ACTIFS           │  │        │  │  Comptes BLOQUÉS          │  │
│  │  - statut = 'actif'       │  │        │  │  - statut = 'bloque'      │  │
│  │  - type = 'epargne'       │  │        │  │  - archived_at != null    │  │
│  │  - type = 'cheque'        │  │        │  │  - dateFinBlocage         │  │
│  │  - deleted_at = null      │  │        │  └───────────────────────────┘  │
│  └───────────────────────────┘  │        │                                 │
│                                 │        │  Données dénormalisées:         │
│  Endpoint:                      │        │  - Client nom/email/téléphone   │
│  GET /api/v1/comptes            │        │  - Toutes infos du compte       │
│                                 │        │                                 │
│  Recherche rapide               │        │  Endpoint:                      │
│  Performances optimales         │        │  GET /api/v1/comptes/archive    │
│                                 │        │                                 │
│                                 │        │  Archive long terme             │
│                                 │        │  Coûts réduits                  │
└─────────────────────────────────┘        └─────────────────────────────────┘
         ▲                                              │
         │                                              │
         │            DÉBLOCAGE (Job quotidien)         │
         └──────────────────────────────────────────────┘
                    RESTAURATION AUTOMATIQUE
```

## 📝 Légende

- `|` : Flux synchrone
- `->` : Appel de méthode/requête
- `<-` : Retour de méthode/réponse
- `-->` : Requête base de données
- `<--` : Résultat base de données
