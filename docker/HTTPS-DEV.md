# Local HTTPS dev setup

Apple Pay + Google Pay buttons only render when:

1. The page is served over HTTPS (Safari refuses to load wallets on `http://`).
2. The storefront domain is registered with Stripe (auto-handled by
   `StripeConnectService::registerPaymentMethodDomain` when a tenant
   completes Express onboarding).
3. The browser is Safari (macOS/iOS) — Chrome/Firefox auto-hide Apple Pay.

This guide covers (1) — running the stack with a trusted HTTPS cert
on `*.ganvo.lvh.me` so you can test the full checkout flow with real
wallet buttons locally.

## One-time setup

```bash
# 1. Install mkcert (macOS):
brew install mkcert nss

# 2. Install mkcert's local CA into the macOS keychain — Safari only
#    trusts certs signed by CAs in the system trust store, so this
#    step is what makes the whole thing work.
mkcert -install

# 3. Generate a wildcard cert for the dev domains:
mkcert \
  -cert-file docker/certs/ganvo.lvh.me.crt \
  -key-file  docker/certs/ganvo.lvh.me.key \
  '*.ganvo.lvh.me' 'ganvo.lvh.me'
```

The certs are gitignored — every developer generates their own,
signed by their own local CA.

## Run with HTTPS

```bash
# Start Caddy alongside the app:
docker compose --profile https up -d

# Stop just Caddy (keep app running):
docker compose --profile https stop https
```

When the `https` profile is up:

| URL pattern | What happens |
|---|---|
| `https://acme.ganvo.lvh.me/`    | Caddy serves HTTPS:443 → reverse-proxies to app:8000 |
| `http://acme.ganvo.lvh.me:8000/` | Still works — app's bare HTTP listener untouched |

The `:8000` listener stays for non-HTTPS use cases (Stripe CLI
forwarding to webhooks, plain dev iteration without certificates,
etc.). HTTPS is purely additive.

## After bringing HTTPS up

**Update `.env`** so URL generation + redirects use HTTPS by default:

```dotenv
APP_URL=https://ganvo.lvh.me
```

Then clear the config cache:

```bash
docker exec ganvo php artisan config:clear
```

Caddy's reverse_proxy sets `X-Forwarded-Proto: https` on every request
+ `bootstrap/app.php` is configured to trust the proxy, so even
without updating `APP_URL` the `url()` helper will generate `https://`
links. The env-level change just makes background jobs (which don't
have an inbound request to infer from) emit correct links too.

## Testing Apple Pay

1. **Connect a test merchant.** Go to `https://acme.ganvo.lvh.me/store/payments`,
   click "Set up Ganvo Payments", complete Express onboarding with
   Stripe's test data.
2. **Domain registration fires automatically** when onboarding completes
   (`PaymentsController::handleReturn`). The `account.updated` webhook
   from Stripe also triggers it edge-on-charges-enabled.
3. **Use Safari on macOS** with an Apple Pay test card in Wallet:
   - Open Wallet → Add Card → use Stripe's test card 4242 4242 4242 4242
   - Stripe's docs cover this in detail: https://stripe.com/docs/testing#regulatory-cards
4. **Go to the storefront, add an item, hit checkout.** The Payment
   Element should show "Pay with Apple Pay" at the top.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Apple Pay button doesn't appear at all | Safari requires HTTPS + a registered domain. Confirm both. Reload after registration; Stripe needs ~30 s to validate. |
| Browser shows "Not Secure" / cert error | `mkcert -install` wasn't run, OR Safari is using an old cert from before mkcert was installed. Fully quit Safari and reopen. |
| Forms POST to `http://...:8000` while page is on HTTPS | `APP_URL` still set to `http://`. Update `.env` + `php artisan config:clear`. |
| `docker compose --profile https up` says port 443 is in use | Something else on the host already binds :443 (sometimes the system's apachectl). Stop it, or change the published port to `443:443` → `8443:443` in `docker-compose.yml`. |
