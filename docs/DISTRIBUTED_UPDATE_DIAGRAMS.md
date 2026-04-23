# Distributed Update Mechanism - Architecture Diagrams

## Diagram 1: Complete System Architecture Overview

```mermaid
graph TB
    subgraph GitHub["GitHub Repository"]
        direction TB
        GH_Release["Release v1.2.0"]
        GH_Notes["Release Notes + Code"]
        GH_Release -.->|tag| GH_Notes
    end
    
    subgraph LaptopA["Laptop A (Development)"]
        direction TB
        A_Code["Codebase v1.0.0"]
        A_DB["Database local_dcms_a"]
        A_Dev["Developer Work"]
        A_Dev -->|commit| A_Code
        A_Code -->|git push| GitHub
    end
    
    subgraph LaptopB["Laptop B (Staging)"]
        direction TB
        B_Code["Codebase v1.0.0"]
        B_DB["Database local_dcms_b"]
        B_UI["Settings/Updates Page"]
        B_Check["UpdateChecker Service"]
        B_Execute["UpdateExecutor Service"]
        B_Backup["BackupService"]
        
        B_UI -->|triggers| B_Check
        B_Check -->|queries| GitHub
        B_Check -->|stores| B_DB
        B_UI -->|user clicks| B_Execute
        B_Execute -->|creates| B_Backup
        B_Execute -->|applies| B_Code
        B_Execute -->|runs migrations| B_DB
    end
    
    subgraph Cache["Local Cache Layer"]
        direction TB
        C_File["Cache Files"]
        C_Redis["Redis/Memory"]
        C_DB["Database Cache"]
    end
    
    LaptopB -->|reaches| Cache
    LaptopB -->|internet| GitHub
    
    style GitHub fill:#333,stroke:#fff,color:#fff
    style LaptopA fill:#e1f5ff,stroke:#01579b
    style LaptopB fill:#f3e5f5,stroke:#4a148c
    style Cache fill:#fff3e0,stroke:#e65100
```

---

## Diagram 2: Update Detection Flow

```mermaid
flowchart TD
    A["User Opens Settings/Updates"] -->|page loads| B["Check for Updates<br/>Button"]
    B -->|click| C{Is GitHub<br/>Reachable?}
    
    C -->|Yes| D["Fetch Latest Release<br/>AppVersionService"]
    C -->|No| E["Use Local Cache"]
    
    D -->|API Success| F["Parse Release Data<br/>v1.2.0"]
    D -->|API Timeout| E
    
    F -->|JSON Response| G["Create SystemRelease<br/>Record"]
    E -->|Cached Data| G
    
    G -->|Compare| H{Current < Latest?}
    
    H -->|No| I["Display: No Updates"]
    H -->|Yes| J{Check<br/>Requires DB<br/>Update?}
    
    J -->|Yes| K["⚠️ Show WARNING Banner<br/>Database Migration Needed"]
    J -->|No| L["✅ Show SUCCESS Banner<br/>Ready to Install"]
    
    K -->|features| M["UpdateNotificationBanner.vue"]
    L -->|features| M
    
    M -->|render| N["Dashboard Update Widget"]
    N -->|display| O["User Sees Notification"]
```

---

## Diagram 3: Update Execution Pipeline

```mermaid
sequenceDiagram
    participant U as User
    participant UI as Update Page
    participant SVC as Update Service
    participant BAK as Backup Service
    participant GIT as Git/Download
    participant PHP as PHP Artisan
    participant DB as Database
    participant LOG as Audit Log
    
    U->>UI: Click Install Update
    UI->>SVC: executeUpdate(release)
    
    SVC->>SVC: Pre-flight Checks
    alt Check Fails
        SVC-->>UI: Error: Insufficient Disk Space
        UI-->>U: Show Error Message
    end
    
    SVC->>BAK: Create Backup
    BAK->>DB: Dump Database
    BAK-->>SVC: Backup ID: backup_xyz
    
    SVC->>GIT: Download Release
    GIT-->>SVC: Code Downloaded
    
    SVC->>SVC: Verify Integrity
    
    SVC->>PHP: artisan optimize:clear
    
    alt Requires Migration
        SVC->>PHP: artisan tenants:migrate
        PHP->>DB: Execute Migrations
        DB-->>PHP: Migrations Complete
    end
    
    SVC->>SVC: Verify Version Match
    
    alt Verification Success
        SVC->>LOG: Record Success
        SVC-->>UI: Update Complete
        UI-->>U: ✅ Update Installed!
    else Verification Fails
        SVC->>BAK: Restore Backup (backup_xyz)
        BAK->>DB: Restore Database
        SVC->>LOG: Record Failure + Rollback
        SVC-->>UI: Update Failed (Restored)
        UI-->>U: ❌ Rollback Complete
    end
```

---

## Diagram 4: Update Notification Hierarchy

```mermaid
graph TD
    Release["New Release<br/>v1.2.0"]
    
    Release -->|Assess| Urgency{Urgency<br/>Level}
    
    Urgency -->|Security<br/>Patch| Critical["🔴 CRITICAL<br/>Red Banner<br/>Mandatory"]
    Urgency -->|Bug Fix| High["🟡 HIGH<br/>Yellow Banner<br/>Recommended"]
    Urgency -->|Feature| Medium["🔵 MEDIUM<br/>Blue Banner<br/>Optional"]
    Urgency -->|Patch| Low["⚪ LOW<br/>Badge Only<br/>Background"]
    
    Critical -->|Display| C_UI["In-App Banner<br/>Email Alert<br/>Dashboard"]
    High -->|Display| H_UI["In-App Banner<br/>Dashboard"]
    Medium -->|Display| M_UI["Dashboard Widget<br/>Sidebar Badge"]
    Low -->|Display| L_UI["Sidebar Badge Only"]
    
    C_UI -->|User Sees| User1["Install Now"]
    H_UI -->|User Sees| User2["Install Soon"]
    M_UI -->|User Sees| User3["Optional Install"]
    L_UI -->|User Sees| User4["Quiet Update"]
    
    style Critical fill:#ffcccc,stroke:#c33
    style High fill:#ffeecc,stroke:#f59e0b
    style Medium fill:#ccddff,stroke:#3b82f6
    style Low fill:#eeeeee,stroke:#999
```

---

## Diagram 5: Backup & Rollback Strategy

```mermaid
graph LR
    A["Current State<br/>v1.0.0"] -->|Start Update| B["Create Backup<br/>backup_xyz"]
    
    B -->|Backup Created| C["Download<br/>v1.2.0"]
    
    C -->|Execute| D{Update<br/>Successful?}
    
    D -->|Yes ✅| E["✅ Update Complete<br/>v1.2.0 Active"]
    D -->|No ❌| F["🔄 Automatic Rollback"]
    
    F -->|Restore| G["Restore from<br/>backup_xyz"]
    
    G -->|Database Restored| H["❌ Update Failed<br/>v1.0.0 Restored"]
    
    E -->|Archive| I["Keep Backup<br/>30 days"]
    H -->|Alert Admin| J["Send Alert Email"]
    
    style A fill:#e3f2fd
    style E fill:#c8e6c9
    style H fill:#ffcccc
    style B fill:#fff9c4
    style F fill:#ffe0b2
```

---

## Diagram 6: Multi-Laptop Independence Model

```mermaid
graph TB
    subgraph Laptop_A["Laptop A"]
        A1["v1.0.0"]
        A2["Database"]
        A1 -.->|independent| A2
    end
    
    subgraph Laptop_B["Laptop B"]
        B1["v1.0.0"]
        B2["Database"]
        B1 -.->|independent| B2
    end
    
    GitHub["GitHub<br/>v1.2.0 Released"]
    
    GitHub -->|API Call| B_Check["Check for Updates"]
    B_Check -->|Compare| B_Decide{Update Now?}
    
    B_Decide -->|Yes| B_Update["Update to v1.2.0"]
    B_Decide -->|No| B_Wait["Stay on v1.0.0"]
    
    B_Update -->|Update| B1
    
    Laptop_A -->|No Sync| Laptop_B
    
    GitHub -.->|No Push| Laptop_A
    
    Note1["Laptop A<br/>unaware of update"]
    Note2["Laptop B<br/>updated independently"]
    
    Laptop_A -.-> Note1
    Laptop_B -.-> Note2
    
    style Laptop_A fill:#e1f5ff
    style Laptop_B fill:#f3e5f5
    style GitHub fill:#333,color:#fff
    style Note1 fill:#ffcccc,color:#000
    style Note2 fill:#ccffcc,color:#000
```

---

## Diagram 7: Error Handling & Fallback Chain

```mermaid
graph TD
    Start["Update Check Initiated"] -->|Primary| Step1{"GitHub API<br/>Available?"}
    
    Step1 -->|Yes| A["Fetch Latest<br/>Release"]
    Step1 -->|No| B["Fallback 1:<br/>Use Memory Cache"]
    
    A -->|Success| Display["Display Update"]
    A -->|Timeout| B
    
    B -->|Have Cache?| C{Cache<br/>Fresh?}
    C -->|Yes| Display
    C -->|No| D["Fallback 2:<br/>Database Cache"]
    
    D -->|Data Found| E["Use DB Version<br/>Show Warning"]
    D -->|Empty| F["Fallback 3:<br/>Local File"]
    
    E --> Display
    
    F -->|Found| G["Use File Version<br/>Show Warning"]
    F -->|Not Found| H["Fallback 4:<br/>Hardcoded Default"]
    
    G --> Display
    
    H -->|Last Resort| I["Show: No Updates<br/>Unable to Check"]
    I --> Display
    
    Display -->|User Sees| J["Clear Status"]
    
    style Step1 fill:#e3f2fd
    style A fill:#c8e6c9
    style B fill:#fff9c4
    style C fill:#fff9c4
    style D fill:#ffe0b2
    style F fill:#ffcccc
    style H fill:#ffcccc
    style I fill:#ffcccc
```

---

## Diagram 8: Release Tagging Convention

```mermaid
graph LR
    Release["New Release"]
    
    Release -->|Tag| Version["v1.2.3"]
    
    Release -->|Notes| Notes["Release Notes"]
    
    Version -->|Semantic| SemVer["1 = Major<br/>2 = Minor<br/>3 = Patch"]
    
    Notes -->|Contains| HasMigration{Contains<br/>[MIGRATION]?}
    
    HasMigration -->|Yes| Flag1["⚠️ requires_db_update<br/>= true"]
    HasMigration -->|No| Flag2["✅ requires_db_update<br/>= false"]
    
    Flag1 -->|Store| DB1["SystemRelease<br/>Table"]
    Flag2 -->|Store| DB1
    
    DB1 -->|Affects| UI["Update Notification<br/>Shows Warning?"]
    
    style Version fill:#e3f2fd
    style SemVer fill:#c8e6c9
    style Flag1 fill:#ffcccc
    style Flag2 fill:#c8e6c9
    style DB1 fill:#f3e5f5
    style UI fill:#fff9c4
```

---

## Diagram 9: User Decision Tree for Updates

```mermaid
graph TD
    A["Update Notification<br/>Appears"] --> B{"How<br/>Urgent?"}
    
    B -->|🔴 Critical| C["Install Immediately<br/>Security Risk"]
    B -->|🟡 Important| D{"Have 30<br/>min?"}
    B -->|🔵 Optional| E{"Interested<br/>in Features?"}
    
    C --> F["Backup Automatic"]
    D -->|Yes| F
    D -->|No| G["Schedule for Later"]
    E -->|Yes| F
    E -->|No| H["Dismiss"]
    
    F -->|Ready| I["Execute Update"]
    I -->|Check| J{DB<br/>Migration?}
    J -->|Yes| K["Run tenants:migrate"]
    J -->|No| L["Skip Migration"]
    K --> M["✅ Complete"]
    L --> M
    M --> N["Show Success"]
    
    G --> O["Update Queued<br/>for Tonight"]
    H --> P["Check Again Later"]
    N --> Q["Return to Dashboard"]
    O --> Q
    P --> Q
    
    style C fill:#ffcccc
    style D fill:#fff9c4
    style E fill:#ccddff
    style I fill:#c8e6c9
    style M fill:#c8e6c9
    style Q fill:#f0f0f0
```

---

## Diagram 10: Performance & Scalability View

```mermaid
graph TB
    subgraph Performance["Performance Characteristics"]
        direction TB
        A["GitHub API Check: 100-500ms"]
        B["Cache Hit: <5ms"]
        C["Create Backup: 30-120s"]
        D["Apply Update: 1-5 minutes"]
        E["Total Time: 5-15 minutes"]
    end
    
    subgraph Scalability["Scalability (Multi-tenant)"]
        direction TB
        F["Single Release Sync: O(1)"]
        G["Tenant Notification: O(n)"]
        H["Parallel Migrations: O(1)"]
        I["Queue-based Execution"]
    end
    
    subgraph Bottlenecks["Potential Bottlenecks"]
        direction TB
        J["Large DB Backup"]
        K["Network Timeout"]
        L["Concurrent Updates"]
        M["Disk Space"]
    end
    
    subgraph Solutions["Solutions"]
        direction TB
        N["Incremental Backups"]
        O["Retry with Backoff"]
        P["Queue Manager"]
        Q["Cleanup Policy"]
    end
    
    J -->|solve| N
    K -->|solve| O
    L -->|solve| P
    M -->|solve| Q
    
    style Performance fill:#c8e6c9
    style Scalability fill:#c8e6c9
    style Bottlenecks fill:#ffcccc
    style Solutions fill:#fff9c4
```

---

