# Asimon Font Files

This directory should contain the Asimon font files from Multi Digital's design system.

## Required Files

Add the following font files to this directory:

- **asimon-regular.otf** — Regular weight (400)
- **asimon-medium.otf** — Medium weight (500)
- **asimon-bold.otf** — Bold weight (700)

## Setup Instructions

1. Obtain the Asimon font files from Multi Digital
2. Place the .otf files in this directory
3. Uncomment the @font-face declarations in `style.css`:
   - Look for the "Fonts" section at the top of style.css
   - Uncomment the three @font-face blocks for Asimon

## Without Asimon Fonts

The theme will work without these files by falling back to system fonts:
- Arial
- Segoe UI
- BlinkMacSystemFont
- -apple-system

This allows the theme to be installed immediately, with Asimon fonts as an optional enhancement.

---

For more information, see the README.md in the theme root.
