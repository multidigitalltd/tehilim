# הוראות התקנת תבנית Tehilim

## דרישות מוקדמות
- WordPress 5.9+
- PHP 7.4+
- WP-CLI (אופציונלי אך מומלץ)

## שלבי התקנה

### 1. הורדה והעלאה
```bash
# אם משתמשים ב-FTP:
1. הורידו את tehilim-theme-2.0.0.zip
2. חלצו את התיקיה: tehilim-theme/
3. העלו ל-wp-content/themes/
```

### 2. הפעלה ב-WordPress Admin
```
1. בדף Dashboard → Appearance → Themes
2. חפשו "Tehilim"
3. לחצו "Activate"
4. התבנית תיצור בעצמה את טבלת wp_tehilim_recitations
```

### 3. הגדרות (אופציונליות)
```
1. Dashboard → Tehilim → Settings
2. הזינו תיאור האתר
3. (אופציונלי) הפעילו Turnstile CAPTCHA:
   - הוסיפו ל-wp-config.php:
   
   define('TURNSTILE_SITE_KEY', 'your-site-key');
   define('TURNSTILE_SECRET_KEY', 'your-secret-key');
```

### 4. קישורים קבועים
```
1. Dashboard → Settings → Permalinks
2. בחרו Custom Structure
3. לחצו Save Changes (זה יפרסם מחדש את הכללים)
```

## תוכן ה-ZIP

```
tehilim-theme-2.0.0.zip
├── tehilim-theme/                 # התבנית הראשית
│   ├── style.css                  # סגנונות ראשיים
│   ├── functions.php              # התקנה ותצורה
│   ├── front-page.php             # דף הבית
│   ├── single-campaign.php        # עמוד קמפיין
│   ├── archive-campaign.php       # רשימת קמפיינים
│   ├── page-create.php            # יצירת קמפיין
│   ├── template-ambassador.php    # דף שגריר
│   ├── template-dashboard.php     # לוח בקרה
│   ├── header.php                 # כותרת
│   ├── footer.php                 # כותרת תחתונה
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   ├── theme.css          # עיצוב מלא
│   │   │   └── reset.css          # איפוס CSS
│   │   ├── js/
│   │   │   ├── app.js             # לוגיקה הקורא
│   │   │   └── form.js            # טפסים
│   │   └── fonts/
│   │       ├── asimon-regular.otf # פונט Asimon 400
│   │       ├── asimon-medium.otf  # פונט Asimon 500
│   │       └── asimon-bold.otf    # פונט Asimon 700
│   │
│   └── inc/
│       ├── cpt.php                # סוגי פוסטים וטקסונומיה
│       ├── meta.php               # שדות מטא וחישובים
│       ├── rest.php               # REST API endpoints
│       └── admin.php              # תפריטי וב אדמין
│
├── CLAUDE.md                      # תיעוד מפתחים
├── COMPLIANCE_REPORT.md           # דוח ביטחון מלא
└── SECURITY_FIXES_SUMMARY.md      # סיכום תיקונים
```

## בדיקות בסיסיות

```
✅ בדיקת התקנה:
1. Dashboard → Campaigns - רשימה ריקה = התקנה הצליחה
2. Dashboard → Ambassadors - רשימה ריקה = התקנה הצליחה
3. בדוק בדוא"ל: wp_tehilim_recitations ✓

✅ בדיקת פעולה:
1. יצור קמפיין חדש (Create Campaign)
2. שנה שגריר (Join as Ambassador)
3. אמור פרק (I Said This button)
4. בדוק שהנתונים נשמרו בעמוד הקמפיין
```

## הגדרות מומלצות

### wp-config.php
```php
// ביטחון
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);

// Turnstile CAPTCHA (אופציונלי)
define('TURNSTILE_SITE_KEY', 'your-key-here');
define('TURNSTILE_SECRET_KEY', 'your-secret-here');

// Cache (מומלץ)
define('WP_CACHE', true);
```

### החסנת נתונים (Caching)
מומלץ להתקין:
- WP Super Cache
- Redis Object Cache
- W3 Total Cache

## פתרון בעיות

### הטבלה לא נוצרה
```
1. בדקו שהתבנית מופעלת
2. בדקו הרשאות מסד נתונים
3. ידנית: השתמשו בצו:
   wp db query < tehilim-recitations.sql
```

### Turnstile לא עובד
```
1. בדקו ש-TURNSTILE_SITE_KEY ו-TURNSTILE_SECRET_KEY קיימים
2. בדקו ב-wp-content/debug.log
3. בדקו עם Cloudflare dashboard
```

### קיצורי הקישור לא עובדים
```
1. Dashboard → Settings → Permalinks
2. בחרו Custom Structure
3. לחצו Save Changes
4. נסו שוב
```

## עדכונים וביטחון

ודא שאתה מפעיל את הגרסה האחרונה ביותר תמיד.

## תמיכה וביטחון

לשאלות או בעיות ביטחון:
📧 multidigitalltd@gmail.com

---

**גרסה**: 2.0.0  
**תאריך**: 2026-07-23  
**מצב**: מוכן לייצור
