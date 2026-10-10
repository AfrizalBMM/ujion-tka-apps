# Backend Audit Report — redirect-tka-apps (branch: fix/backend-audit)

## CRITICAL Issues

### C1. Doku Payment Notification — No IP Whitelist / Origin Verification
- **File:** `routes/api.php:40-42`, `app/Services/DokuService.php:204-234`
- **Type:** Security
- **Severity:** Critical
- **Description:** The Doku notification webhook (`/api/payments/doku/notification`) relies solely on HMAC signature verification (`verifyNotificationSignature`). While signature verification is present and uses `hash_equals`, there is no IP allowlist for Doku's callback IPs. If the `doku_secret_key` leaks or is weak, an attacker can forge notifications and mark transactions as paid without actual payment. The signature check is the only line of defense — no defense-in-depth (IP whitelist, rate-limiting beyond `throttle:120,1`).
- **Recommended Fix:** Add Doku's official callback IP ranges to an allowlist middleware, or verify the notification source against Doku's documented IPs in addition to signature verification.

### C2. User Model — `access_token` Exposed in Inertia Shared Props
- **File:** `app/Http/Middleware/HandleInertiaRequests.php:55`, `app/Models/User.php:67-71`
- **Type:** Security
- **Severity:** Critical
- **Description:** `HandleInertiaRequests::share()` exposes `$user->access_token` to the frontend Inertia props (`'access_token' => $user->access_token`). While `access_token` is listed in `$hidden` (line 70) for JSON serialization, Inertia's `array_filter` share array is manually constructed — it bypasses Eloquent's `$hidden` by reading the property directly. This means the access token is sent to the browser on every page load for authenticated guru users. The token is used as a login credential (siswa login via token), so this is a sensitive credential leak.
- **Recommended Fix:** Remove `access_token` from the Inertia shared data array. If the frontend genuinely needs it for API calls, use a scoped, short-lived token instead of the persistent access token. At minimum, do not include it in shared props for all pages.

### C3. Trial Service `checkExpiry` Called on Every Request — Race Condition + Unnecessary Writes
- **File:** `app/Http/Middleware/EnsureGuruAccountIsActive.php:50`, `app/Http/Middleware/EnsureTrialActive.php:44`, `app/Services/TrialService.php:59-71`
- **Type:** Bug / N+1 (write amplification)
- **Severity:** High
- **Description:** Both `EnsureGuruAccountIsActive` and `EnsureTrialActive` call `$this->trialService->checkExpiry($user)` on every single request. `checkExpiry` does a `SELECT` + potentially an `UPDATE` + `$user->fresh()` (another `SELECT`) on every request if the user's trial is active. For a guru with an active trial, every page load triggers 2-3 extra queries and potentially an UPDATE. When both middleware run (the guru route group has both `guru.active` and `trial.active`), `checkExpiry` is called **twice** per request — once in `EnsureGuruAccountIsActive` (line 50) and once in `EnsureTrialActive` (line 44). The second call is redundant because the first already checked and refreshed.
- **Recommended Fix:** Memoize the expiry check result within the request lifecycle, or only run `checkExpiry` in one middleware. Consider caching the trial status in session with a short TTL (e.g., 5 minutes) instead of checking the DB every request.

### C4. `EnsureGuruAccountIsActive` — Dead Code: Trial Expired Passes Through
- **File:** `app/Http/Middleware/EnsureGuruAccountIsActive.php:57-60`
- **Type:** Logic Error
- **Severity:** High
- **Description:** After calling `checkExpiry` and refreshing the user (line 51), if `isTrialActive()` is false (line 53), the code checks `isTrialExpired()` on line 58. If the trial is expired, it calls `return $next($request)` — granting full access. The comment says "serahkan ke middleware trial.active (redirect ke pricing)" but `EnsureTrialActive` is listed AFTER `guru.active` in the middleware stack (`routes/guru.php:44`). So `guru.active` lets the expired-trial user through, expecting `trial.active` to catch it. This works only if `trial.active` always runs after `guru.active`. If `trial.active` is ever removed or reordered from a route, expired-trial gurus get full access. This is fragile implicit dependency between middleware.
- **Recommended Fix:** Don't pass through in `guru.active` for expired trials — redirect to pricing directly, or make the middleware ordering dependency explicit with documentation. At minimum, add a guard: if trial is expired AND account_status is pending, redirect rather than pass through.

### C5. `EnsureGuruJenjangAccess` — N+1 Lazy Loading on Every Guru Jenjang Route
- **File:** `app/Http/Middleware/EnsureGuruJenjangAccess.php:30`
- **Type:** N+1 Query
- **Severity:** High
- **Description:** Line 30: `$jenjangKode = $mapel?->paketSoal?->jenjang?->kode ?? $paket?->jenjang?->kode;` — This chains three lazy-loaded relationships (`paketSoal`, then `jenjang` on paketSoal, or `jenjang` on paket). For routes that only bind `paket` (not `mapel`), this triggers `$paket->jenjang->kode` = 1 lazy query. For routes that bind both `mapel` and `paket`, it triggers `$mapel->paketSoal` (1 query) + `$mapel->paketSoal->jenjang` (another query). The route model binding doesn't eager-load these relationships. On every single request to `guru.jenjang`-protected routes, this generates 1-3 extra queries.
- **Recommended Fix:** Ensure route model bindings eager-load the `jenjang` relationship. In route definitions, use `->with('jenjang')` or in the controller's `resolveRouteBinding`. Alternatively, cache the jenjang check.

### C6. `EnsureRole` Middleware — Non-Guru/Superadmin Users Get 403 Instead of Redirect
- **File:** `app/Http/Middleware/EnsureRole.php:18-24`
- **Type:** Logic Error
- **Severity:** Medium
- **Description:** When an unauthenticated user hits a `role:guru` route, they get `abort(403)` (line 19) instead of a redirect to login. The middleware checks `if (! $user)` and aborts 403. The standard Laravel pattern is to redirect unauthenticated users to login (which the `auth` middleware handles when placed before `role`). However, if `role` middleware is somehow used without `auth` preceding it, the user gets a 403 error page rather than a login redirect. The route definitions in `guru.php:37` and `guru.php:44` do include `auth` before `role`, so this is mitigated by ordering — but it's fragile.
- **Recommended Fix:** Return `redirect()->guest()->route('login')` for unauthenticated users instead of `abort(403)`, or document that `auth` must always precede `role`.

## HIGH Issues

### H1. `HandleInertiaRequests::guruLayoutProps` — N+1 Queries on Every Page Load
- **File:** `app/Http/Middleware/HandleInertiaRequests.php:73-131`
- **Type:** N+1 Query
- **Severity:** High
- **Description:** `guruLayoutProps()` runs on every Inertia page load for guru users and executes:
  1. `PricingPlan::resolveForJenjang($user->jenjang)` (line 80) — 1-2 queries (checks column existence + queries plan)
  2. `Jenjang::where('kode', $user->jenjang)->value('nama')` (line 84) — 1 query
  3. `$user->transactions()->where(...)->value('doku_invoice_number')` (line 93) — 1 query
  4. `AppSetting::getValue('wa_group_link')` (line 128) — 1 query
  5. `AuditLog::where('user_id', $user->id)...->limit(8)->get()` (line 110) — 1 query
  Total: 5-6 queries on every page load for every guru, even when `paymentLocked` is false (queries 1-3 are inside `if ($paymentLocked)` but query 4 and 5 always run). The `AppSetting::getValue` call also runs `Schema::hasTable` each time.
- **Recommended Fix:** Cache `AppSetting` values and `Jenjang` lookups. Only run audit log + payment queries when `paymentLocked` is true. Move audit log query outside the always-executed path.

### H2. `HandleInertiaRequests::superadminLayout` — Unindexed Count Query Every Page Load
- **File:** `app/Http/Middleware/HandleInertiaRequests.php:68`
- **Type:** Performance / N+1
- **Severity:** High
- **Description:** `Transaction::where('status', Transaction::STATUS_PENDING)->count()` runs on every page load for superadmin users. This is a `COUNT(*)` query filtering on `status = 'pending'`. The transactions table migration does not have an index on `status` alone (only implicit via the `enum` default). As the transactions table grows, this count query becomes expensive on every superadmin navigation.
- **Recommended Fix:** Add an index on `status` column, or cache the pending count with a short TTL (e.g., 60 seconds via `Cache::remember`).

### H3. `AuditRequest` Middleware — Audit Log Written on Every Non-GET-`/up` Request
- **File:** `app/Http/Middleware/AuditRequest.php:18-39`
- **Type:** Performance
- **Severity:** High
- **Description:** The `AuditRequest` middleware writes an `AuditLog::create()` on every request where the method is not GET-to-`/up`. Wait — re-reading: line 24 checks `if ($request->isMethod('get') && $request->is('up'))` — this only skips GET requests to `/up`. **All other requests including GET requests to other paths** trigger an audit log INSERT. This means every page navigation (GET) by every user creates a new `AuditLog` row. For a high-traffic app, this table will grow explosively. The `path` column is `string` (not text) and `user_agent` is `text` — the `sanitizeUserAgent` function truncates to a family + SHA1 hash, but the volume of inserts is the concern.
- **Recommended Fix:** Either only audit non-GET (mutating) requests, or add a daily/weekly cleanup job (there is an `audit-logs.cleanup` route, so consider scheduling it). Also consider sampling or batching audit writes.

### H4. `PaymentApprovalService::approve` — No Coupon Usage Recording on Payment Approval
- **File:** `app/Services/PaymentApprovalService.php:37-53`, `app/Services/CouponService.php:111-129`
- **Type:** Logic Error
- **Severity:** High
- **Description:** When a transaction with a coupon is approved via `PaymentApprovalService::approve()`, the transaction status is set to `STATUS_SUCCESS` and the teacher is activated, but `CouponService::recordUsage()` is never called within the approval flow. This means coupon usage counts may never be recorded if the approval happens through this service (manual approval path). The `verify()` method checks usage limits, but if `recordUsage` is only called elsewhere (e.g., during Doku callback), then manually-approved transactions with coupons will have unrecorded usage — allowing the coupon to be used beyond its limit.
- **Recommended Fix:** Ensure `CouponService::recordUsage()` is called within `PaymentApprovalService::approve()` when the transaction has a `coupon_id`, or verify it's called in the Doku callback path and document that the manual approval path also records it.

### H5. `DokuService::verifyNotificationSignature` — Path Mismatch Risk
- **File:** `app/Services/DokuService.php:228`
- **Type:** Security / Bug
- **Severity:** High
- **Description:** Line 228 uses `$request->getPathInfo()` to build the signature. However, the notification route is registered in `routes/api.php:40` as `/api/payments/doku/notification` (under the `api` middleware group). `getPathInfo()` returns the path **without** the `/api` prefix if the route is loaded via the API routes file with `Route::post('/payments/doku/notification', ...)`. Actually, in Laravel 11 with `withRouting(api: ...)`, the api routes are prefixed with `/api` by default. So `getPathInfo()` returns `/api/payments/doku/notification`. But the Doku callback might be configured to hit a different path. If the configured callback URL in Doku differs from what `getPathInfo()` returns (e.g., due to a proxy/load balancer stripping the prefix), signature verification will always fail — silently rejecting legitimate payment notifications.
- **Recommended Fix:** Verify that the path used in signature computation matches exactly what Doku sends. Log the received path during testing. Consider using a fixed string constant for the expected path rather than `getPathInfo()`.

## MEDIUM Issues

### M1. `CouponService::verify` — TOCTOU Race Condition on Usage Limits
- **File:** `app/Services/CouponService.php:56-69`
- **Type:** Race Condition / Logic Error
- **Severity:** Medium
- **Description:** `verify()` checks `max_usage_total` (line 56-61) and `max_usage_per_user` (line 64-68) by counting existing usages. Between the `verify()` call and the subsequent `recordUsage()` call, another request could pass the same check — allowing the coupon to exceed its usage limit. There is no `lockForUpdate` or atomic increment. This is a classic Time-Of-Check-to-Time-Of-Use race.
- **Recommended Fix:** Wrap the verify + recordUsage in a database transaction with `lockForUpdate` on the coupon row, or use an atomic counter / unique constraint approach.

### M2. `CouponService::countUserUsage` — Guest User Identifier Spoofing
- **File:** `app/Services/CouponService.php:143-156`
- **Type:** Security
- **Severity:** Medium
- **Description:** For guest users (no `$user`), usage is counted by `identifier` (nomor_wa or email). A guest can use a different identifier each time to bypass per-user limits. There is no verification that the identifier actually belongs to the user making the request.
- **Recommended Fix:** For guest coupon usage, require OTP/verification of the identifier, or tie the identifier to the order/transaction being created.

### M3. `Transaction` Model — `tarifJenjang` and `pricingPlan` Are Duplicate Relationships
- **File:** `app/Models/Transaction.php:62-80`
- **Type:** Code Quality / Confusion
- **Severity:** Medium
- **Description:** Both `pricingPlan()` (line 62) and `tarifJenjang()` (line 77) are `belongsTo(PricingPlan::class, 'pricing_plan_id')` — identical relationships with different names. This is confusing and may lead to inconsistent eager-loading (e.g., code loading `pricingPlan` but templates using `tarifJenjang`).
- **Recommended Fix:** Remove `tarifJenjang()` and use `pricingPlan()` consistently, or document why both exist.

### M4. `Exam::mapels()` — Lazy Relationship Access Without Guard
- **File:** `app/Models/Exam.php:55-58`
- **Type:** Bug / N+1
- **Severity:** Medium
- **Description:** `mapels()` calls `$this->paketSoal->mapels()` which accesses `$this->paketSoal` as a dynamic property (lazy-loads the relationship). If `paketSoal` is null (e.g., exam has no paket_soal_id), this will throw a `Error: Call to a member function mapels() on null`. There's no null check.
- **Recommended Fix:** Add a null guard: `return $this->paketSoal?->mapels() ?? collect();` or use a `HasManyThrough` relationship directly.

### M5. `ParticipantAnswer::question()` — Hardcoded `Question` Model, Ignores `PersonalQuestion`
- **File:** `app/Models/ParticipantAnswer.php:26-29`
- **Type:** Bug
- **Severity:** Medium
- **Description:** The `question()` relationship is hardcoded to `Question::class` (line 28), with a comment saying "or PersonalQuestion depending on context, assuming Question for now." If participant answers can reference personal questions (which the `user_id`-scoped `PersonalQuestion` model suggests), this relationship will fail to resolve those records.
- **Recommended Fix:** Use a polymorphic relationship (`morphTo`) or separate the question types with a discriminator column.

### M6. `DokuService::createCheckoutPayment` — Retry Uses Different Invoice Number Without Updating Transaction First
- **File:** `app/Services/DokuService.php:79-107`
- **Type:** Logic Error
- **Severity:** Medium
- **Description:** If the first payment request fails (line 81), a new invoice number is generated (line 82) and a second request is sent (line 83). If the second succeeds, the transaction is updated with the new invoice number (line 104-107). But if the first request actually **succeeded on Doku's side** (e.g., timeout but Doku processed it), the first invoice number is now orphaned — a payment exists on Doku for an invoice number that's not recorded in the transaction. When the notification comes for the first invoice number, it won't match any transaction.
- **Recommended Fix:** Before retrying with a new invoice number, check if the first request actually created a payment on Doku's side via a status check.

### M7. `WhatsAppService::normalizeNumber` — Incomplete International Number Handling
- **File:** `app/Services/WhatsAppService.php:23-52`
- **Type:** Bug
- **Severity:** Medium
- **Description:** The normalization logic strips all non-digits, then checks for `8` prefix (Indonesian mobile without 0) or `62` prefix. But numbers starting with `0` get the `0` stripped (line 32: `ltrim($digits, '0')`), which handles `08xxx → 8xxx → 628xxx`. However, international numbers (e.g., `+1 555-123-4567` for a US number) would get mangled: digits `15551234567`, doesn't start with `8` or `62`, so returns `15551234567` — which is wrong for the WA gateway expecting E.164 format. The function only handles Indonesian numbers correctly.
- **Recommended Fix:** This is acceptable if the app only serves Indonesian users, but document the assumption. Consider validating input is an Indonesian number before normalization.

### M8. `WaMessageTemplateService::getBody` — N+1 on Template Fetch
- **File:** `app/Services/WaMessageTemplateService.php:201-215`
- **Type:** N+1 Query
- **Severity:** Medium
- **Description:** `getBody()` queries the database for a `WaMessageTemplate` on every call. When sending batch WA messages (e.g., trial reminders to many users), each `render()` call triggers a DB query for the same template key. There is no caching.
- **Recommended Fix:** Cache the template by key within the request lifecycle, or preload all templates once for batch operations.

### M9. Route — `guru.php:44` Middleware Order: `guru.active` Before `trial.active` Causes Redundant Logic
- **File:** `routes/guru.php:44`
- **Type:** Logic Error / Redundancy
- **Severity:** Medium
- **Description:** The middleware stack is `['auth', 'role:guru', 'guru.active', 'trial.active', 'audit']`. `EnsureGuruAccountIsActive` (guru.active) already checks trial status, trial expiry, and account status comprehensively (lines 44-60). Then `EnsureTrialActive` (trial.active) runs and does the **exact same checks again** — `checkExpiry`, `isTrialActive`, `isTrialExpired` (lines 44-56). This is redundant double-processing. The `EnsureGuruAccountIsActive` middleware is designed to be a superset of `EnsureTrialActive` — running both is wasteful and confusing.
- **Recommended Fix:** Either remove `trial.active` from the stack (since `guru.active` already handles trial logic), or split responsibilities cleanly so each middleware has a single concern.

### M10. Route — `payments/pembahasan/status/{examSession}` Missing Throttle
- **File:** `routes/web.php:59`
- **Type:** Security (DoS)
- **Severity:** Medium
- **Description:** `Route::get('/payments/pembahasan/status/{examSession}', ...)` has no throttle middleware. The `start` route (line 58) has `throttle:10,1` but the status endpoint (likely polled by frontend) is unprotected. A malicious user could hammer this endpoint to enumerate exam sessions or cause DB load.
- **Recommended Fix:** Add `throttle:30,1` or similar rate limiting to the status endpoint.

### M11. Route — `ujian-online/pay/status` and `pay/finish` Missing Throttle
- **File:** `routes/web.php:73-74`
- **Type:** Security (DoS)
- **Severity:** Medium
- **Description:** The public exam payment status and finish endpoints have no throttle middleware. Only `pay.start` (line 72) is throttled. The status/finish endpoints likely trigger Doku API calls or DB updates and are publicly accessible.
- **Recommended Fix:** Add throttle middleware to `pay.status` and `pay.finish` routes.

### M12. Route — API Chat Routes Missing Role Check
- **File:** `routes/api.php:30-34`
- **Type:** Security
- **Severity:** Medium
- **Description:** The chat API routes (`/api/chat/threads`, `/api/chat/send`, `/api/chat/messages/{thread}`) only have `auth` middleware. There's no `role` middleware. The controller does check `isGuru()` / `isSuperadmin()` internally (ChatController lines 106-125, 185-197), but a `siswa` user with a valid session can still call these endpoints and get a 403 JSON response — the access check is in the controller rather than middleware. While functionally safe (controller blocks it), it's inconsistent with the web routes that use `role:superadmin` / `role:guru` at the middleware level.
- **Recommended Fix:** Add `role:superadmin` or a custom middleware that allows guru+superadmin to the chat API routes, for defense-in-depth and consistency.

## LOW Issues

### L1. `AuditLog` Model — Manual `created_at` in Fillable, No `$timestamps` Control
- **File:** `app/Models/AuditLog.php:9-25`
- **Type:** Code Quality
- **Severity:** Low
- **Description:** `created_at` is in `$fillable` (line 18) and cast to datetime (line 23). The model also casts `updated_at` (line 24) but the middleware only sets `created_at` values. Since the table has `$table->timestamps()` in the migration (line 21 of migration), both `created_at` and `updated_at` are managed by Eloquent. Having `created_at` in `$fillable` allows manual override which could lead to inconsistent timestamps. Also `user_id` is cast to `integer` (line 22) — this will cast `null` to `0` instead of preserving null.
- **Recommended Fix:** Remove `created_at` from `$fillable`. Remove `user_id` integer cast (it's nullable, integer cast on null gives 0 which is misleading).

### L2. `EnsureGuruAccountIsActive` — `$user->fresh()` After `checkExpiry` May Return Null
- **File:** `app/Http/Middleware/EnsureGuruAccountIsActive.php:51-53`
- **Type:** Bug (edge case)
- **Severity:** Low
- **Description:** After `$this->trialService->checkExpiry($user)` (which calls `$user->update()` then `$user->fresh()`), the middleware reassigns `$user = $user->fresh()` (line 51). If the user was deleted between the original load and `fresh()`, `$user` becomes `null`. Line 53 then calls `$user->isTrialActive()` — a null pointer error. The same pattern exists in `EnsureTrialActive.php:45-48`.
- **Recommended Fix:** Add a null check after `fresh()`: `if (! $user) { Auth::logout(); return redirect()->route('login'); }`.

### L3. `LandingExamOrder` — Session Token Generated with `Str::random(80)` but Column is `varchar(80)`
- **File:** `app/Models/LandingExamOrder.php:50`, migration `2026_09_03_010300`
- **Type:** Bug (edge case)
- **Severity:** Low
- **Description:** `Str::random(80)` generates an 80-character string. The migration defines `session_token` as `string('session_token', 80)->unique()`. `Str::random(80)` generates exactly 80 characters, so it fits. However, if the random string generator ever produces a collision (extremely unlikely with 80 chars), the `unique` constraint will throw a QueryException. There is no retry loop like the ones in `ExamMapelToken::generateUniqueToken` and `MaterialPracticeToken::generateUniqueToken`.
- **Recommended Fix:** Add a retry loop for session_token generation to handle the (extremely rare) unique constraint violation.

### L4. `MaterialPracticeToken::regeneratePackages` — `each()` + `delete()` Doesn't Cascade Pivot
- **File:** `app/Models/MaterialPracticeToken.php:78-81`
- **Type:** Bug
- **Severity:** Low
- **Description:** `$this->packages()->each(function (MaterialPracticePackage $package) { $package->questions()->detach(); $package->delete(); })` — this deletes pivot rows then the package. However, `MaterialPracticePackageAttempt` has a `belongsTo` package relationship. If packages have attempts, deleting the package will leave orphaned `MaterialPracticePackageAnswer` and `MaterialPracticePackageAttempt` records (no `onDelete('cascade')` on the FK). The migration should be checked, but the model doesn't explicitly handle cascading.
- **Recommended Fix:** Ensure migration has `cascadeOnDelete` on `material_practice_package_id` FKs, or explicitly delete attempts before deleting packages.

### L5. `HandleInertiaRequests` — `errors` Closure Uses `getBag('default')` Without Null Check
- **File:** `app/Http/Middleware/HandleInertiaRequests.php:62-64`
- **Type:** Bug (edge case)
- **Severity:** Low
- **Description:** Line 62-64: `fn () => $request->session()->get('errors') ? $request->session()->get('errors')->getBag('default')->getMessages() : (object) []`. This assumes the errors bag always has a 'default' bag. While Laravel's default ErrorBag is 'default', custom error bags could exist. If the session has an errors object without a 'default' bag, `getBag('default')` throws.
- **Recommended Fix:** Use `optional($request->session()->get('errors'))->getBag('default')?->getMessages() ?? (object) []`.

### L6. `CouponService::calculateDiscount` — Free Coupon Returns Full Amount Without Min Check
- **File:** `app/Services/CouponService.php:87-104`
- **Type:** Logic Error (minor)
- **Severity:** Low
- **Description:** For a `free` type coupon, `calculateDiscount` returns `$amount` (line 90), making the discounted amount 0. But the `min_transaction` check in `verify()` (line 43) still applies to the original amount. If a free coupon has `min_transaction` set, and the amount is below the minimum, the coupon is rejected. This may be intentional (free coupons require a minimum spend), but the behavior is surprising — a "free" coupon that requires a minimum transaction is counterintuitive.
- **Recommended Fix:** Document this behavior or skip `min_transaction` check for free coupons.

### L7. `Chat::booted` — `forgetDisk('local')` on Every Chat Deletion
- **File:** `app/Models/Chat.php:37-43`
- **Type:** Performance (minor)
- **Severity:** Low
- **Description:** The `deleting` event calls `$filesystem->forgetDisk('local')` on every chat deletion. `forgetDisk` clears the resolved disk instance from the manager's cache. This is unnecessary per-deletion and may cause the disk to be re-resolved (re-read config) on the next access within the same request.
- **Recommended Fix:** Remove the `forgetDisk` call — it's not needed for file deletion. The disk resolution is cached by the manager and doesn't affect file operations.

### L8. `DokuService::isTimestampFresh` — 5-Minute Window Without Clock Skew Tolerance
- **File:** `app/Services/DokuService.php:325-338`
- **Type:** Bug (edge case)
- **Severity:** Low
- **Description:** `isTimestampFresh` allows ±5 minutes between the request timestamp and server time. If the server clock is slightly off from Doku's servers, legitimate notifications could be rejected. Doku's documentation may specify a different freshness window.
- **Recommended Fix:** Verify against Doku's API docs for the expected timestamp tolerance. Consider using UTC consistently (the timestamp is already parsed as UTC).

### L9. Route — `superadmin` Group Uses POST for Destructive Actions Instead of DELETE
- **File:** `routes/web.php:188, 196, 215, 230, 231, etc.`
- **Type:** Code Quality
- **Severity:** Low
- **Description:** Many destructive routes (delete blog post, delete testimonial, delete coupon, delete material, destroy-all) use `POST /.../delete` instead of `DELETE`. While this works (and Inertia forms often use POST for simplicity), it deviates from RESTful conventions. The `paket-soal` routes (lines 292-295) correctly use `DELETE` for some routes, showing inconsistency within the same route file.
- **Recommended Fix:** Standardize on `DELETE` for destructive actions, or document the POST-based convention consistently.

### L10. `GlobalQuestion::scopeForMaterial` — Fallback Matching Ignores `jenjang_id`
- **File:** `app/Models/GlobalQuestion.php:50-63`
- **Type:** Logic Error
- **Severity:** Low
- **Description:** The `forMaterial` scope matches by `material_id` first, then falls back to matching unlinked questions by `material_mapel` + `material_curriculum` (line 59-60). However, it doesn't filter by `jenjang_id` in the fallback. This means a question from SD level could be included in an SMA material's question pool if they share the same `mapel` and `curriculum` values.
- **Recommended Fix:** Add `->where('jenjang_id', $material->jenjang_id)` or `->where('jenjang', $material->jenjang)` to the fallback query.

### L11. `PricingPlan::resolveForJenjang` — Multiple Schema::hasColumn Calls Per Invocation
- **File:** `app/Models/PricingPlan.php:35-60`
- **Type:** Performance
- **Severity:** Low
- **Description:** `resolveForJenjang` calls `Schema::hasColumn('pricing_plans', 'jenjang')` up to 3 times per invocation (lines 43, 53). Each `Schema::hasColumn` query hits the information schema. This is called from `HandleInertiaRequests::guruLayoutProps` on every page load for locked guru users.
- **Recommended Fix:** Cache the column existence check, or check once at application boot. Since the column is added by a migration, it's always present after migration — consider removing the runtime check entirely.

### L12. `EnsureGuruAccountIsActive::routeAllowedForPending` — Empty Route Name Treated as Allowed
- **File:** `app/Http/Middleware/EnsureGuruAccountIsActive.php:100-104`
- **Type:** Security (minor)
- **Severity:** Low
- **Description:** Line 102: `if ($routeName === '' || $routeName === 'guru.dashboard') { return true; }` — if a route has no name (empty string), it's allowed for pending accounts. An unnamed route (which shouldn't normally exist in this route group, but could exist via a fallback or error) would bypass the pending account restriction.
- **Recommended Fix:** Change to `if ($routeName === 'guru.dashboard')` (don't treat empty as allowed). Or explicitly deny unnamed routes for pending accounts.

## Summary

| Severity | Count |
|----------|-------|
| Critical | 6 |
| High | 6 |
| Medium | 12 |
| Low | 12 |
| **Total** | **36** |

### Top Priority Fixes
1. **C2** — Remove `access_token` from Inertia shared props (credential leak)
2. **C1** — Add IP allowlist for Doku webhook notification
3. **C3/C4/M9** — Fix redundant trial check middleware (double execution + fragile dependency)
4. **C5/H1/H2** — Fix N+1 queries in middleware (every page load)
5. **H4** — Ensure coupon usage is recorded on payment approval
6. **H5** — Verify Doku notification signature path matches callback URL
