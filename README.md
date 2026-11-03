# WP Testimonial Slider

A responsive WordPress testimonial slider with a highlighted active slide.

- **Active slide highlight** - the selected testimonial gets a full-colour, enlarged photo, an accent border and a raised card. The others fade back.
- **Adaptive slide counts** - 5-6 slides on desktop, 3-4 on tablet, 1-2 on mobile. All configurable.
- **Custom content** - name, role, company, link, star rating, photo (featured image) and quote per testimonial.
- **Zero dependencies** - a small vanilla JavaScript slider; no jQuery or external libraries on the front end.
- **Settings screen** (Testimonials -> Slider Settings) and a **widget**.
- **Shortcode** with per-instance overrides.

## Installation

1. Copy the `wp-testimonial-slider` folder into `wp-content/plugins/`, or upload the ZIP via **Plugins -> Add New -> Upload Plugin**.
2. Activate the plugin.
3. Go to **Testimonials -> Add New** to create entries. Set the **Featured image** to use a photo.
4. Tune the layout in **Testimonials -> Slider Settings** (or click **Import sample testimonials** to try it instantly).
5. Add the slider to any page with:

```
[wp_testimonial_slider]
```

## Shortcode options

Every setting can be overridden per shortcode:

```
[wp_testimonial_slider desktop="6" tablet="4" mobile="2" autoplay="true" autoplay_speed="5000" loop="true" dots="true" arrows="true" center_mode="true" gap="24" category="clients"]
```

| Attribute        | Default | Description                                    |
| ---------------- | ------- | ---------------------------------------------- |
| `desktop`        | 5       | Slides visible on desktop                      |
| `tablet`         | 3       | Slides visible on tablet                       |
| `mobile`         | 1       | Slides visible on mobile                       |
| `autoplay`       | true    | Auto-advance slides                            |
| `autoplay_speed` | 4000    | Delay between slides (ms)                      |
| `speed`          | 500     | Transition speed (ms)                          |
| `loop`           | true    | Infinite loop                                  |
| `arrows`         | true    | Show previous / next arrows                    |
| `dots`           | true    | Show pagination dots                           |
| `center_mode`    | true    | Highlight and centre the active slide          |
| `show_photo`     | true    | Show the photo                                 |
| `show_quote`     | true    | Show the testimonial text                      |
| `show_name`      | true    | Show the name                                  |
| `show_role`      | true    | Show role / company                            |
| `show_rating`    | true    | Show the star rating                           |
| `gap`            | 24      | Space between slides (px)                       |
| `category`       | -       | Testimonial group slug(s), comma separated     |
| `orderby`        | menu_order | `menu_order`, `date`, `title`, `rand`, `ID` |
| `limit`          | 12      | Maximum testimonials (0 = all)                 |

## File structure

```
wp-testimonial-slider.php        Plugin bootstrap
includes/
  class-wts-plugin.php           Wires components together
  class-wts-post-type.php        Post type, taxonomy, meta boxes
  class-wts-settings.php         Settings screen + sample importer
  class-wts-shortcode.php        Query + markup rendering
  class-wts-widget.php           Sidebar widget
assets/
  css/wts-slider.css             Front-end styles
  js/wts-slider.js               Front-end slider (vanilla JS)
  css/admin.css                  Admin styles
  js/admin.js                    Admin scripts
uninstall.php                    Cleanup on uninstall
readme.txt                       WordPress.org readme
```

## License

GPL-2.0-or-later.
