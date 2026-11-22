# Dauns Menu

Shortcodes, styling and behaviour for the Dauns menu page, kept in a plugin so a
theme update can't take the menu with it.

## Install

Copy this folder into `wp-content/plugins/` and activate **Dauns Menu**. The menu
page uses the theme's `template-menu.php` template.

## Editing the menu

The menu is an ordinary page. Dishes are written inside shortcodes, in the order
they appear on the page:

    [menu-section title="Small plates"]

    [menu-item name="Soup of the day" price="6.50"]Ask at the counter.[/menu-item]

    [menu-item name="Smoked trout" tags="gf" img="trout.jpg" price="9.00"]With
    horseradish creme fraiche and rye.[/menu-item]

    [/menu-section]

### Quoting

One character needs escaping: a double quote, written as `&quot;`. Everything
else — `&`, apostrophes, `ö`, `å`, `é` — is typed the way you would normally type
it.

Anything between a `[menu-item]` and its closing `[/menu-item]` is the dish
description.

## Shortcodes

| Shortcode | What it does |
| --- | --- |
| `[menu-section title="..."]` | One section of the menu. `badge="..."` adds a small chip beside the heading. |
| `[menu-item name="..."]` | A dish. `price="..."` (or `eatin="..."` / `takeaway="..."` for two prices), `img="..."` (file name or attachment ID), `alt="..."`, `featured="1"` for a full-width photo card, `tags="..."`, `soldout="1"` to dim the row. |
| `[menu-sub title="..."]` | A sub-heading inside a section. `note="..."` adds a line under it. |
| `[menu-option label="..."]` | A choice under a dish, e.g. sizes. `note="..."` adds a note beside it. `opts="..."` on the dish titles the list. |
| `[menu-note]` | A cream panel for a message from the kitchen. |
| `[menu-deal]` | A blue banner for an offer. |
| `[menu-nav]` | The jump nav. Place it once, near the top of the page. |

## Photos

Dish photos live in the media library. Use the file name (`trout.jpg`) or the
attachment ID. Photos are cropped square: 140px beside a dish row, or a
full-width card when the dish is marked `featured="1"`.

## Tags

`gf`, `gfo`, `nfo`, `nuts`, `alc`, `spicy`. Anything else is ignored rather than
printed, so a typo can't put a broken chip on a live menu.
