# Dauns Menu

Shortcodes, styling and behaviour for the Dauns menu page, kept in a plugin so a
theme update can't take the menu with it.

## Install

Copy this folder into `wp-content/plugins/` and activate **Dauns Menu**. The menu
page uses the theme's `template-menu.php` template.

## Editing the menu

The menu is an ordinary page. Dishes are written inside shortcodes, in the order
they appear on the page.

### Quoting (workaround, for now)

While I chase this down: if a label comes out cut short, avoid the quote. Write
`Half portion with chips` rather than `Half "portion" with chips`, or use a
hyphen instead. Plain text is unaffected.
