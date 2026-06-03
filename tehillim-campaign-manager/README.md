# Tehillim Campaign Manager 3.0

מערכת קמפיינים לחלוקת ספרי תהילים לוורדפרס — נכתבת מחדש מאפס (clean-room) בארכיטקטורה מודרנית, נגישה ותקנית.

## סטטוס

**v3.0 — בבנייה.** מונח כעת **שלד ארכיטקטוני** מותקן-ורץ; הפיצ'רים נבנים מעליו בשלבים (PRs).

## ארכיטקטורה

- מרחב שמות `TCM\` עם autoload (PSR-4) דרך Composer + fallback מובנה (רץ גם בלי `composer install`).
- מודולים נטענים ב‑`src/Plugin.php`; כל מודול מממש `TCM\Contracts\Registerable`.
- מפת הקוד המלאה: [`src/README.md`](src/README.md).

## פיתוח

```bash
composer install      # התקנת כלי איכות
composer lint         # PHPCS (WordPress-Coding-Standards)
composer analyze      # PHPStan
```

CI (GitHub Actions) מריץ php-lint + PHPCS + PHPStan על כל push/PR.

## מסמכים

- [`../docs/UPGRADE-PLAN.md`](../docs/UPGRADE-PLAN.md) — תכנית השדרוג המלאה (אבטחה, נגישות, פונקציונליות, עסקי, רשימות/וואטסאפ, פרסום, סגולות).
- [`../docs/DESIGN-BRIEF.md`](../docs/DESIGN-BRIEF.md) — בריף עיצוב UX/UI למעצב/ת.
- [`../docs/ENGINEERING-STANDARDS.md`](../docs/ENGINEERING-STANDARDS.md) — תקן הנדסי מחייב (Production Reality / Definition of Done).

## דרישות

- WordPress 6.0+
- PHP 7.4+

## לאחר התקנה

הגדרות → קישורים קבועים → שמירת שינויים (כדי לרשום את מבנה ה‑permalinks).
