# Ganvo — White-Label E-Commerce & Web Platform

## Project Overview
Ganvo is a white-label SaaS platform with two client types under one roof:

1. **Storefronts** — clients self-onboard through a guided wizard, pick a storefront theme, customize their store, manage products, and go live — all managed through a permission-based admin system.
2. **Custom websites (hub)** — hand-built client sites that live in their **own repos and hosting**; Ganvo manages the client relationship only: registry (live URL, repo, stack, notes), billing via the shared tenant/subscription stack, up/down checks, and SA oversight (Super Admin → Clients → Websites). Tenants carry a `type` (`store` | `website`); website tenants have no Store row, and their platform subdomain 302-redirects to the live site once a URL is set. Currently registered: Kass Photography, Midi BG, ASG (repos under ~/Projects/PHP/, untouched by the platform).

> **Domain note:** the platform runs on **ganvo.bg** as the primary domain.

---

## Core Features

### 1. Client Onboarding Wizard (Before Store Builder)
Step-by-step flow every new client goes through:
1. **Sign up** — email + password
2. **Verify email**
3. **Business info** — store name, business type, contact details
4. **Pick a subscription plan** *(placeholder — tiers to be defined later)*
5. **Theme picker** — live preview of available themes
6. **Customize appearance** — colors, fonts, logo upload
7. **Add first products**
8. **Go live** 🎉

### 2. Theme Marketplace
- 2 themes to start (expandable later)
- Live theme preview before selecting
- Per-theme customization:
  - Color palette
  - Font selection
  - Logo upload

### 3. Client Store Builder (Self-Serve)
- Product listing manager (add / edit / delete products, images, pricing)
- Branding & domain configuration
- Accessible after onboarding is complete

### 4. Payment Processing (Stripe Connect)
- Each client connects their own Stripe account during onboarding
- Customer purchases go **directly to the client's Stripe account**
- Platform owner has **read-only visibility** on all orders across all stores (for support & oversight)
- Two payment flows:
  - **End customer → Client** (product purchases via Stripe Connect)
  - **Client → Ganvo** (subscription fees — details TBD)

### 5. Subscription Plans *(To Be Defined Later)*
- Feature-gated tiers (e.g. Starter, Pro, Business)
- Managed via Stripe Subscriptions
- Plan is selected during onboarding (step 4) but details TBD
- Architecture should be ready to support tiers from day one

### 6. Admin Panel — Permission-Based (Two Roles)

**Super Admin (Ganvo Owner):**
- View and manage all client accounts
- Read-only visibility on all orders across all stores (for support)
- Manage client subscriptions and billing
- Suspend / activate stores
- View platform-wide revenue and analytics

**Store Admin (Client):**
- Access only their own store
- Manage products, orders, and appearance
- View their own store analytics
- Connect and manage their Stripe account

---

## Tech Stack (To Be Decided)
Suggestions to consider:
- **Frontend:** Next.js (App Router) + Tailwind CSS
- **Backend:** Next.js API routes or Node.js/Express
- **Database:** PostgreSQL with Prisma or Drizzle ORM
- **Auth:** Clerk or NextAuth (with role-based permissions)
- **Payments:** Stripe Connect (client payouts) + Stripe Subscriptions (platform fees)
- **File Storage:** Cloudinary or AWS S3 (logo/product images)
- **Hosting:** Vercel (frontend) + Railway or Supabase (database)

---

## Project Structure (Proposed)
```
/
├── apps/
│   ├── web/              # Ganvo marketing/landing page
│   ├── dashboard/        # Super admin + client admin panel
│   └── storefront/       # Client-facing storefronts (dynamic per client)
├── packages/
│   ├── ui/               # Shared UI components
│   ├── db/               # Database schema & migrations
│   └── stripe/           # Stripe Connect + Subscriptions helpers
└── AGENTS.md
```

---

## Key Business Rules
- Each client has one storefront
- Clients can only access their own data (enforced at API level)
- Super admin has read-only access to all orders (no write access to client stores)
- Payments go directly to clients via Stripe Connect
- Subscription tier determines which features are unlocked (tiers TBD)
- Storefronts are multi-tenant (served from shared codebase, isolated by client ID)
- All admin permissions must be enforced server-side, not just on the frontend

---

## Database

**Dev default:** SQLite (`database/database.sqlite`). Zero setup, single file in the repo.

**Switch to MySQL when:** you hit production traffic, need concurrent writes, want managed backups, or any feature wants real JSON indexing.

### How to switch (≈ 5 minutes)

1. Edit `.env` — comment out `DB_CONNECTION=sqlite` and uncomment the MySQL stanza (already there with values matching the compose profile).
2. Start the MySQL container alongside the app:
   ```bash
   docker compose --profile mysql up -d
   ```
3. Run migrations against the fresh MySQL instance:
   ```bash
   docker exec ganvo php artisan migrate:fresh --seed
   ```

No Docker rebuild needed — `pdo_mysql` is baked into the image. The MySQL service is opt-in via the `mysql` compose profile, so SQLite users aren't paying for it.

To move existing SQLite data: `sqlite3 database/database.sqlite .dump > dump.sql`, hand-tweak SQL dialect differences (mostly quoting), then `mysql -u ganvo -p ganvo < dump.sql`. For a serious migration use Laravel's own tooling (`php artisan db:seed` with extracted fixtures) or a script per-table.

---

## What's Not Yet Decided
- Subscription tier details (pricing, feature breakdown per tier)
- Whether clients get subdomains (client.ganvo.bg) or custom domains
- Hosting & domain strategy (to be discussed after core build)
- Analytics scope (basic vs. advanced)

---

## Development Priorities (Suggested Order)
1. Auth system with role-based permissions (Super Admin / Store Admin)
2. Client onboarding wizard (sign up → verify → business info → plan → theme → customize → products → go live)
3. Theme engine (2 themes, live preview, customization)
4. Product management (CRUD)
5. Stripe Connect integration (client payouts)
6. Stripe Subscriptions (platform fees — placeholder tier)
7. Storefront rendering (per-client, theme-aware)
8. Super admin dashboard (platform-wide order visibility + client management)
9. Client store admin dashboard (orders, products, analytics)
10. Analytics
11. Subscription tiers (once defined)
