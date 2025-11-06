# Quick Reference: Auto Increment Values

## Table Auto Increment Mapping (Descending by Record Count)

```
1.  activity_logs           → 100,001  (500K+ records)
2.  event_registrations     → 200,001  (100K+ records)
3.  sessions                → 70,001   (50K+ records)
4.  family_members          → 1,001    (10-25K records)
5.  payments                → 2,001    (5-10K records)
6.  event_tickets           → 3,001    (5-10K records)
7.  uploads                 → 6,001    (2-5K records)
8.  user_providers          → 4,001    (1-2K records)
9.  password_resets         → 5,001    (500-1K records)
10. webauthn_credentials    → 801      (500-1K records)
11. users                   → 101      (500-1K records)
12. events                  → 301      (100-200 records)
13. families                → 201      (200-400 records)
14. sponsorships            → 401      (50-100 records)
15. coupons                 → 501      (20-50 records)
16. roles                   → 11       (5-10 records - system)
```

## Estimated Data Volume (Year 1 vs 5-Year)

| Rank | Table | Est. Y1 | 5-Year | Start ID | Range |
|------|-------|---------|--------|----------|-------|
| 1 | activity_logs | 500K | 1M+ | 100,001 | 6-digit |
| 2 | event_registrations | 100K | 500K+ | 200,001 | 6-digit |
| 3 | sessions | 50K | 200K+ | 70,001 | 5-digit |
| 4 | family_members | 10-25K | 50K | 1,001 | 4-digit |
| 5 | payments | 5-10K | 25K | 2,001 | 4-digit |
| 6 | event_tickets | 5-10K | 50K | 3,001 | 4-digit |
| 7 | uploads | 2-5K | 10K | 6,001 | 4-digit |
| 8 | user_providers | 1-2K | 10K | 4,001 | 4-digit |
| 9 | password_resets | 500-1K | 5K | 5,001 | 4-digit |
| 10 | webauthn_credentials | 500-1K | 5K | 801 | 3-digit |
| 11 | users | 500-1K | 5K | 101 | 3-digit |
| 12 | events | 100-200 | 500 | 301 | 3-digit |
| 13 | families | 200-400 | 1K | 201 | 3-digit |
| 14 | sponsorships | 50-100 | 500 | 401 | 3-digit |
| 15 | coupons | 20-50 | 200 | 501 | 3-digit |
| 16 | roles | 5-10 | 20 | 11 | 2-digit |

## Sorting by Start Value

| Start ID | Table | Est. Y1 Records |
|----------|-------|-----------------|
| 11 | roles | 5-10 |
| 101 | users | 500-1K |
| 201 | families | 200-400 |
| 301 | events | 100-200 |
| 401 | sponsorships | 50-100 |
| 501 | coupons | 20-50 |
| 601 | uploads | 2-5K |
| 801 | webauthn_credentials | 500-1K |
| 1,001 | family_members | 10-25K |
| 2,001 | payments | 5-10K |
| 3,001 | event_tickets | 5-10K |
| 4,001 | user_providers | 1-2K |
| 5,001 | password_resets | 500-1K |
| 70,001 | sessions | 50K |
| 100,001 | activity_logs | 500K |
| 200,001 | event_registrations | 100K |

## Migration File
- Location: `database/migrations/2025_11_06_set_auto_increment.sql`
- Status: Ready to apply
- Order: Descending by record count (largest to smallest)
- Command: `mysql -u root umashaktidham < database/migrations/2025_11_06_set_auto_increment.sql`
