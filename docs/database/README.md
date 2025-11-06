# 🗄️ Database Documentation

This section contains database schema, migrations, and design documentation.

## 📄 Files

### DATABASE_AUTO_INCREMENT_STRATEGY.md
**Comprehensive Auto-Increment Strategy**

Strategy for assigning AUTO_INCREMENT values to all 16 tables:

**3-Tier System:**
- **2-digit (11):** System tables (roles - 5-10 records)
- **5-digit (10001-90001):** Secondary tables (events, sponsorships, uploads, etc.)
- **6-digit (100001-1200001):** Core business tables (users, families, sessions, etc.)
- **8-digit (10000001+):** High-volume transaction tables (activity_logs, event_registrations, payments)

Each tier provides growth runway for 5+ years.

### MIGRATION_AUTO_INCREMENT_SUMMARY.md
Visual summary of the auto-increment strategy:
- ASCII diagram showing all 16 tables
- 5-year growth projections
- Migration SQL statements
- Key statistics and rationale

### AUTO_INCREMENT_QUICK_REFERENCE.md
Quick lookup table for AUTO_INCREMENT values:
- Sorted by table ID
- Starting values and growth projections
- Easy-to-scan format for developers

---

## 🎯 Migration Status

**File:** `database/migrations/2025_11_06_set_auto_increment.sql`

**Ready to apply:** ✅ Production-ready migration with all 16 tables configured.

---

## 📊 Table Configuration

| Tier | Tables | ID Range | Examples |
|------|--------|----------|----------|
| 8-digit | 3 | 10M-50M | activity_logs, event_registrations, payments |
| 6-digit | 7 | 100K-1.2M | users, families, sessions, etc. |
| 5-digit | 5 | 10K-90K | events, sponsorships, uploads, coupons |
| 2-digit | 1 | 11+ | roles (system) |

---

**Last Updated:** November 6, 2025
