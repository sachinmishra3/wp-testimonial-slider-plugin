=== WP Testimonial Slider ===
Contributors: sachinmishra3
Tags: testimonials, slider, carousel, reviews, client feedback
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A responsive testimonial slider with a highlighted active slide, adaptive slide counts and a full settings screen.

== Description ==

WP Testimonial Slider turns your client feedback into an attractive, responsive carousel.

Features:

* **Highlighted active slide** - the selected testimonial gets an enlarged, full-colour photo, an accent border and a raised card, while the others fade back.
* **Responsive slide counts** - show 5-6 slides on desktop, 3-4 on tablet and 1-2 on mobile. Every count and breakpoint is configurable.
* **Custom testimonials** - each testimonial has a name, role, company, link, star rating, photo (featured image) and quote.
* **No build step, no external libraries** - a small vanilla JavaScript slider.
* **Settings screen** under Testimonials -> Slider Settings.
* **Shortcode** `[wp_testimonial_slider]` with per-instance overrides.
* **Widget** for any widget area.
* **Accessible** - keyboard arrows, focus styles and touch swipe.

== Installation ==

1. Upload the `wp-testimonial-slider` folder to `/wp-content/plugins/`, or install the ZIP through *Plugins -> Add New -> Upload Plugin*.
2. Activate the plugin.
3. Go to **Testimonials -> Add New** and create your testimonials. Set a **Featured image** for the person's photo.
4. Adjust the slider in **Testimonials -> Slider Settings**.
5. Place `[wp_testimonial_slider]` in any post, page or widget.

== Frequently Asked Questions ==

= How do I change how many slides show? =

Go to Testimonials -> Slider Settings and change the desktop, tablet and mobile slide counts.

= How do I show a person's photo? =

Set the **Featured image** on the testimonial. If none is set, the plugin shows a coloured circle with the person's initials.

= Can I override settings for one slider? =

Yes. For example: `[wp_testimonial_slider desktop="6" tablet="4" mobile="2" autoplay="false" center_mode="true"]`.

== Screenshots ==

1. The slider with the active testimonial highlighted.
2. The slider settings screen.

== Changelog ==

= 1.0.0 =
* First release.
