# תכנית שדרוג ופיתוח מקצועית — Tehillim Campaign Manager

מסמך זה מרכז תכנית עבודה מקצועית לשדרוג התוסף בארבעה צירים: **פונקציונליות**, **אבטחה**, **נגישות** ו**איכות קוד (קוד רזה ותקני)**.
התכנית מבוססת על סקירת קוד מלאה של הגרסה הנוכחית.

> **מצב נוכחי:** התוסף כולו מרוכז בקובץ מחלקה יחיד בן ~1,634 שורות
> (`tehillim-campaign-manager/includes/class-tehillim-campaign-manager.php`) המטפל בכל האחריות:
> רישום CPT, סכמת DB, רינדור פרונט, מיילים, וובהוקים, קרון, שגרירים ופאנל ניהול.

---

## 0. תמונת מצב — סיכום מנהלים

| ציר | מצב נוכחי | סיכון/הזדמנות עיקריים |
|-----|-----------|----------------------|
| פונקציונליות | עשירה ועובדת | פעולות משנות-מצב ב‑GET (סיכון prefetch), שליחת מייל המונית סינכרונית, אין REST/AJAX |
| אבטחה | בסיס סביר (nonce + prepare + Turnstile) | PII בטקסט גלוי + אין מנגנון פרטיות (GDPR/חוק הגנת הפרטיות), אין sanitize_callback, סודות מוצגים כ‑text |
| נגישות | חלקית (RTL+lang) | לא עומד ב‑WCAG 2.0 AA / ת"י 5568 — צבע כאמצעי יחיד, labels לא משויכים, אין ARIA |
| איכות קוד | מונוליטי | מחלקה אחת ענקית, HTML/CSS/JS מוטמעים, אפס i18n, אפס בדיקות, אי‑עקביות גרסאות |

**עדיפות מיידית (P0):** תיקון פעולות GET שמשנות מצב, שכבת פרטיות ל‑PII, הצמדת `sanitize_callback`, ותיקוני נגישות חוקיים (ת"י 5568).

---

## 1. אבטחה (P0–P1)

### 1.1 פעולות משנות-מצב מבוצעות ב‑GET ‏(P0)
`handle_done`, `handle_take_more`, `handle_release_chapter` ו‑`handle_pretty_action` מבצעים שינוי במסד דרך בקשת GET עם token בלבד.
סורקי לינקים במייל, אנטי‑וירוס ו‑prefetch של דפדפנים עלולים "לסמן כהושלם" או "לשחרר פרק" **ללא ידיעת המשתמש**.

- **תיקון:** דף ביניים עם אישור (`POST` + nonce) לפני שינוי מצב, או לכל הפחות הגנת `X-Purpose`/אימות מקור והוספת `<meta name="robots" content="noindex,nofollow">` + כותרת `Cache-Control: no-store`.
- שמירה על ה‑token הקיים כאמצעי הרשאה, אך הוצאת הפעולה ההרסנית מ‑idempotent‑GET ל‑POST.

### 1.2 שכבת פרטיות ל‑PII ‏(P0)
שמות, אימיילים וטלפונים נשמרים בטקסט גלוי בטבלאות `tcm_assignments`, וטבלת `tcm_logs` שומרת IP + payload מלא (כולל אימייל) כ‑`LONGTEXT`. אין שילוב עם מנגנון הפרטיות של וורדפרס ואין מדיניות שמירה.

- רישום **WP Privacy Exporter + Eraser** (`wp_privacy_personal_data_exporters` / `_erasers`).
- מדיניות שמירה (retention) + מטלת קרון לגריעת רשומות ולוגים ישנים (הלוגים כיום גדלים ללא הגבלה).
- מיסוך IP/אימייל בלוגים, או שמירת hash בלבד היכן שאפשר.
- הוספת טקסט הסכמה/קישור למדיניות פרטיות בטופס ההצטרפות.

### 1.3 הקשחת הגדרות ‏(P1)
- `register_setting('tcm_settings','tcm_options')` ללא `sanitize_callback` — להוסיף callback שמלבן ומאמת כל שדה (whitelist) במקום הסתמכות על escaping בפלט בלבד.
- שדות סוד (`turnstile_secret_key`, `webhook_secret`) מוצגים כ‑`<input type="text">` — להעביר ל‑`type="password"` ולא להחזיר את הערך המלא לאחר שמירה.
- חתימת וובהוק: `X-TCM-Secret` נשלח כסוד סטטי — לשדרג ל‑HMAC (חתימת גוף הבקשה + timestamp נגד replay).

### 1.4 חיזוקים נוספים ‏(P2)
- השוואת token ב‑constant-time (`hash_equals`).
- Rate limiting קל על `handle_join`/`handle_create_campaign` (transient לפי IP) מעבר ל‑Turnstile.
- ניקוי משאבים: hook ל‑deactivation שמבטל `tcm_cron_tasks`, וקובץ `uninstall.php` (כיום שניהם חסרים).

---

## 2. נגישות — WCAG 2.0 AA / ת"י 5568 (P0–P1)

> אתרים בישראל מחויבים בנגישות לפי ת"י 5568 (מבוסס WCAG 2.0 AA). הפריטים הבאים הם חסמים חוקיים.

### 2.1 צבע כאמצעי מידע יחיד ‏(P0) — WCAG 1.4.1
רשת הפרקים (`shortcode_chapters`) מבדילה סטטוס בצבע בלבד (אפור/צהוב/ירוק) עם `title` בלבד.
- להוסיף **אינדיקציה טקסטואלית/אייקון** לכל אריח (פנוי/נתפס/הושלם) ולא להסתמך על צבע.

### 2.2 שיוך labels לשדות ‏(P0) — WCAG 1.3.1 / 4.1.2
ב‑`shortcode_join_form` ובטפסי הניהול ה‑`<label>` אינו משויך לשדה (`for`/`id`).
- הצמדת `id` לכל שדה + `for` בכל label, `aria-required`, ושיוך הודעות שגיאה לשדה הרלוונטי (`aria-describedby`).

### 2.3 ARIA לפס ההתקדמות ‏(P1) — WCAG 4.1.2
`.tcm-progress` הוא `div` ויזואלי בלבד.
- `role="progressbar"` + `aria-valuenow/min/max` + `aria-label`.

### 2.4 ניגודיות וצבעים מבוקרי-משתמש ‏(P1) — WCAG 1.4.3
צבע טקסט הכפתורים נקבע ע"י המשתמש בלוח הבקרה ללא בדיקת ניגודיות מול צבע הרקע.
- ולידציית ניגודיות בהגדרות + ערכי ברירת מחדל בטוחים, אודיט מלא של הצבעים הקיימים.

### 2.5 פריטים נוספים ‏(P2)
- מפרידי `<option disabled>──────</option>` → `<optgroup>`.
- כפתורי "העתקה" שמשנים טקסט ל"הועתק" → הוספת `aria-live="polite"`.
- ניהול פוקוס: העברת פוקוס לאזור הקריאה לאחר טעינתו.
- אימוג'י/סימנים (🔥, ✓) עם חלופה טקסטואלית (`aria-hidden` + טקסט מקביל).
- הצהרת נגישות וקישור אליה.

---

## 3. פונקציונליות (P1–P2)

### 3.1 שכבת REST/AJAX ‏(P1)
כיום הקריאה מתבצעת ברענון עמוד מלא לכל פרק (`render_reader` + redirect).
- REST API (`register_rest_route`) לסימון "הושלם"/"קח עוד"/שחרור + עדכון פס התקדמות בזמן אמת (polling קל), לחוויית משתמש חלקה ולשילוב עם Elementor/Headless.

### 3.2 שליחת מייל המונית אסינכרונית ‏(P1)
`send_campaign_message_now` ו‑`send_book_completed_notice` שולחים בלולאה סינכרונית בתוך הבקשה — timeout ברשימות גדולות.
- העברת השליחה ל‑**Action Scheduler** (או batches בקרון), הוספת חלק טקסט (plaintext) ל‑multipart, וכותרת `List-Unsubscribe` להודעות המוניות (אנטי-ספאם ודליברביליות).

### 3.3 שיפורי ניהול ‏(P2)
- ייצוא CSV של משתתפים/סטטיסטיקות.
- ניצול `goal_chapters` של שגרירים בממשק (כיום נשמר ולא בשימוש).
- גריעת לוגים אוטומטית + עמוד לוגים עם עימוד וסינון.
- העברת `tcm_pending_messages` מ‑option יחיד גדל לטבלה/CPT ייעודי.

---

## 4. ביצועים וקנה מידה (P1)

- **דפוס N+1:** `shortcode_campaigns` ו‑`admin_dashboard` קוראים ל‑`stats()` בלולאה לכל קמפיין, ו‑`stats()` עצמו מריץ מספר שאילתות `COUNT` נפרדות. להוסיף **caching ב‑transients/object cache** ולאחד שאילתות.
- `generate_round` מריץ 150 `INSERT` בלולאה — להמיר ל‑bulk insert יחיד.
- `posts_per_page => -1` בכמה מקומות — להגביל/לעמד.
- טעינת CSS/JS של הפרונט מתבצעת בכל עמוד (כולל אדמין) — להעמיס מותנה רק כשיש שורטקוד/עמוד קמפיין.

---

## 5. איכות קוד — רזה ותקני (P1–P2)

### 5.1 פירוק המונוליט ‏(P1)
מחלקה אחת בת ~1,634 שורות מפרה SRP. לפצל למרחב שמות `TCM\` עם autoloader (Composer PSR‑4):

```
src/
  Plugin.php            (bootstrap + DI)
  PostType.php          (CPT, permalinks, rewrite)
  Repository/
    AssignmentRepo.php  (DB + dbDelta + queries)
    AmbassadorRepo.php
    LogRepo.php
  Service/
    CampaignService.php (current_round, stats, claim_*)
    MailService.php
    WebhookService.php
    CronService.php
  Admin/
    SettingsPage.php  DashboardPage.php  ChaptersPage.php ...
  Frontend/
    Shortcodes.php  ReaderController.php  JoinController.php
templates/   (קבצי תבנית במקום echo)
assets/css/  assets/js/  (קבצים אמיתיים, לא inline)
```

### 5.2 הפרדת תצוגה ונכסים ‏(P1)
- הוצאת ה‑HTML מ‑`echo` ארוכים לקובצי `templates/` עם escaping.
- הוצאת ה‑CSS/JS המוטמעים (ה‑string הענק ב‑`css()` וה‑JS ב‑`assets()`) לקבצים אמיתיים תחת `assets/` (התיקיות קיימות אך ריקות פרט ל‑README), עם enqueue, גרסאות ו‑minify. שמירת ה‑CSS המבוסס-הגדרות כ‑CSS Custom Properties בלבד inline.

### 5.3 בינאום (i18n) ‏(P1)
`Text Domain` מוצהר אך **אפס** שימוש ב‑`__()`/`esc_html__()` ואין `load_plugin_textdomain`. עוטף את כל המחרוזות ומוסיף קובץ `.pot`. תורם גם ל"תקני".

### 5.4 מקור גרסה יחיד ‏(P1)
אי‑עקביות: כותרת התוסף `2.8.0`, `const VERSION = '2.8.1'`, docblock פנימי `2.6.0`, README `2.6.3`. לאחד למקור אמת יחיד (כותרת התוסף) ולגזור ממנו.

### 5.5 כלים ותקנים ‏(P2)
- **PHPCS** עם `WordPress-Coding-Standards` + **PHPStan/Psalm** ברמה גבוהה.
- **בדיקות:** PHPUnit + `wp-env`/Brain Monkey ללוגיקה הקריטית (`current_round`, `find_empty_full_book_round`, `claim_*`, חישובי `stats`).
- **CI** (GitHub Actions): lint + phpcs + phpstan + tests על כל PR. (ראו §7 לגבי הקמת הסביבה לסשנים.)
- `uninstall.php` + deactivation hook (חסרים כיום).

---

## 6. תכנית ביצוע מומלצת לפי שלבים

| שלב | מיקוד | תכולה | סיכון אם נדחה |
|-----|-------|-------|----------------|
| **שלב 1 (P0)** | אבטחה + נגישות חוקית | §1.1 GET→POST, §1.2 פרטיות בסיסית, §2.1–2.2 צבע+labels | חוקי/PII |
| **שלב 2 (P1)** | תשתית איכות | §5.1 פירוק, §5.3 i18n, §5.4 גרסה, §7 CI+בדיקות | חוב טכני מצטבר |
| **שלב 3 (P1)** | ביצועים + אבטחה מתקדמת | §4 caching, §1.3 sanitize/סודות, §2.3–2.4 ARIA/ניגודיות | קנה מידה |
| **שלב 4 (P1)** | פונקציונליות | §3.1 REST/AJAX, §3.2 מייל אסינכרוני | UX/דליברביליות |
| **שלב 5 (P2)** | ליטוש | §3.3, §2.5, §5.2 תבניות/נכסים, §5.5 כלים | תחזוקתיות |

**עיקרון מנחה:** כל שלב נשלח כ‑PR נפרד וקטן עם בדיקות, תוך שמירה על תאימות לאחור לשורטקודים, ל‑slugs ולסכמת ה‑DB הקיימים (משתמשים ונתונים קיימים בייצור).

---

## 7. תשתית לפיתוח (מומלץ מיידית)

- הוספת **SessionStart hook** ו‑`composer.json` כדי שסביבת הפיתוח (כולל Claude Code on the web) תוכל להריץ lint/phpcs/בדיקות אוטומטית.
- הקמת `phpcs.xml`, `phpstan.neon`, ותיקיית `tests/`.
- workflow ל‑GitHub Actions שמריץ את הבדיקות על כל PR.

---

*מסמך זה הוא תכנית בלבד. כל שינוי בפועל יבוצע ב‑PR נפרד, לאחר אישור סדר העדיפויות.*
