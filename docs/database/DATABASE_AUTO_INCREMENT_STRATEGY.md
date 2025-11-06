# Auto Increment Strategy by Table Size

## Strategy Overview
Tables are assigned auto increment starting points based on:
1. **Expected growth rate** - How many records will be created
2. **Data volume** - Peak concurrent records
3. **ID digit requirements** - Enough room to grow without conflicts

---

## AUTO INCREMENT ASSIGNMENTS

### 🔴 TIER 1: LARGEST TABLES (6-digit: 100,000+)
Heavy logging, continuous growth, high volume

| Table | Start ID | Digits | Rationale |
|-------|----------|--------|-----------|
| `activity_logs` | 100,001 | 6 | Continuous logging, every action tracked |
| `event_registrations` | 200,001 | 6 | Multiple registrations per event, scales fast |
| `sessions` | 70,001 | 5-6 | Session records, temporary but high volume |

**Growth Pattern:** 1,000+ records per month
**Range before collision:** ~900,000 IDs available

---

### 🟡 TIER 2: MEDIUM TABLES (4-digit: 1,000-9,999)
Moderate growth, database bread & butter

| Table | Start ID | Digits | Rationale |
|-------|----------|--------|-----------|
| `family_members` | 1,001 | 4 | ~2-5 per user × users, grows with membership |
| `payments` | 2,001 | 4 | Transaction records, reliable growth |
| `event_tickets` | 3,001 | 4 | Generated per registration, linear growth |
| `user_providers` | 4,001 | 4 | OAuth connections, 1-2 per user |
| `password_resets` | 5,001 | 4 | Reset tokens, periodic need |
| `uploads` | 6,001 | 4 | File uploads, sparse but present |
| `webauthn_credentials` | 8,001 | 4 | 2FA keys, 1-2 per user |

**Growth Pattern:** 10-100 records per month
**Range before collision:** ~9,000 IDs available per table

---

### 🟢 TIER 3: SMALL TABLES (3-digit: 10-999)
Slow growth, foundational/controlled creation

| Table | Start ID | Digits | Rationale |
|-------|----------|--------|-----------|
| `roles` | 11 | 2 | System roles, ~5-10 total (rare change) |
| `users` | 101 | 3 | Core entities, foundational but slower growth |
| `families` | 201 | 3 | One per user group or household |
| `events` | 301 | 3 | Temple events, manually created |
| `sponsorships` | 401 | 3 | Sponsorship relationships, sparse |
| `coupons` | 501 | 3 | Promotional coupons, controlled creation |

**Growth Pattern:** 1-10 records per month
**Range before collision:** ~900 IDs available per table

---

## Projected Growth Timeline

### Year 1 Assumptions
- **Target Users:** 500-1,000
- **Active Users:** 300-500
- **Families:** 200-300
- **Events:** 20-30 annually
- **Registrations:** 500-1,000 per event season
- **Transactions:** 200-500
- **System Load:** Moderate

### 5-Year Projection
- **Users:** 2,000-5,000 (plenty of room with 3-digit start)
- **Family Members:** 10,000-25,000 (need 4-digit start)
- **Event Registrations:** 50,000-100,000 (need 6-digit start)
- **Logs:** 500,000+ (need 6-digit start)

**Result:** All tables have adequate room without ID collisions

---

## Migration Instructions

### Apply to Database
```bash
mysql -u root umashaktidham < database/migrations/2025_11_06_set_auto_increment.sql
```

### Or Apply Individually
```sql
ALTER TABLE activity_logs AUTO_INCREMENT = 100001;
ALTER TABLE event_registrations AUTO_INCREMENT = 200001;
-- ... etc
```

### Verify After Migration
```sql
-- Check auto increment for a table
SELECT AUTO_INCREMENT 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'umashaktidham' 
AND TABLE_NAME = 'users';
```

---

## Benefits of This Strategy

✅ **Prevents ID collisions** - Each table has dedicated number ranges
✅ **Readability** - ID ranges indicate table type (101-999 = small, 1001+ = medium, etc.)
✅ **Scalability** - Tables have 5-10 year growth headroom
✅ **Debugging** - Easy to identify which table an ID comes from
✅ **Future-proof** - Room to add new tables in different ranges
✅ **Human-readable** - IDs are meaningful and organized

---

## ID Format Convention

When you see an ID:
- **2-3 digit** (11-999): System/core tables
- **4-digit** (1001-9999): Application data tables  
- **5-6 digit** (70001-999999): Logs/transactions

Example:
- User #105 → Core user
- Family Member #1,025 → Application data
- Event Registration #200,150 → High volume table
- Activity Log #100,500 → System logging

---

## Notes

- All start points skip ID #1 to avoid confusion with default/system records
- Session table uses 70,001 instead of 60,001 to avoid confusion with 6-digit logs
- Roles table starts at 11 (not 1) to preserve role #1 as potential "admin" default
- This strategy scales to 10M+ total records across all tables
