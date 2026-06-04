# ארכיטקטורת v3.0 — מפת קוד

בנייה מאפס (clean-room), בלי קוד legacy. הקוד מאורגן לפי אחריות (SRP), במרחב שמות `TCM\` עם autoload (PSR-4) דרך Composer, ועם fallback מובנה כך שהתוסף רץ גם בלי `composer install`.

## עץ התיקיות (יעד)

```
src/
  Plugin.php              bootstrap — אוסף "מודולים" (Registerable) ומרשם הוקים
  Activator.php           התקנה: סכמה, אופציות, קרון, rewrite
  Contracts/
    Registerable.php      ממשק מודול שמרשם הוקים
  Support/
    Hebrew.php            עזרי עברית (מספור פרקים)
  Database/
    Schema.php            הגדרות טבלאות (dbDelta)
    *Repository.php       גישה לנתונים (assignments, ambassadors, logs, subscribers, ads)
  PostTypes/
    CampaignPostType.php  CPT קמפיין
    PrayerPostType.php    CPT סגולות/תפילות + טקסונומיה
  Services/               לוגיקה עסקית (Campaign, Round, Stats, Cron, Mail, Webhook, Subscription, Ad)
  Messaging/              שכבת ערוצים מופשטת: MessageChannel + Email + WhatsApp
  Frontend/               Assets, Shortcodes, Controllers, Templating
  Admin/                  Menu, SettingsPage, ניהול פרסום/רשימות
  Rest/                   REST API (סטטוס קמפיין, סימון הושלם, קח עוד)
templates/                תבניות PHP לתצוגה (מופרדות מהלוגיקה)
assets/                   CSS/JS אמיתיים (לא inline)
languages/                קבצי תרגום (.pot)
```

## עקרונות

- **מודולים** מתווספים ב‑`Plugin::modules()`; כל מודול מממש `Registerable::register()`.
- **תצוגה מופרדת** מהלוגיקה: שירותים מחזירים נתונים, תבניות מציגות.
- **i18n** מהיסוד: כל מחרוזת ב‑`__()/esc_html__()` עם text-domain `tehillim-campaign-manager`.
- **נגישות** (ת"י 5568 / WCAG 2.0 AA) ותקני WordPress נאכפים ב‑CI (PHPCS + PHPStan).
- **פרונט:** גוטנברג/בלוקים + REST למסך הקריאה + shortcodes לגיבוי.

ראו `../../docs/UPGRADE-PLAN.md` לתכנית המלאה ו‑`../../docs/DESIGN-BRIEF.md` לבריף העיצוב.
