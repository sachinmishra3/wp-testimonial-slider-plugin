/**
 * WP Testimonial Slider - front-end slider (spotlight layout).
 *
 * A row of photo tiles where the active tile is enlarged and centred, and the
 * active testimonial's quote, name and role are shown beside it.
 */
(function () {
	'use strict';

	var instances = [];

	function defaults() {
		return {
			desktop: 5,
			tablet: 3,
			mobile: 1,
			breakpointTablet: 1024,
			breakpointMobile: 767,
			autoplay: 1,
			autoplaySpeed: 4000,
			speed: 500,
			loop: 1,
			arrows: 1,
			dots: 1
		};
	}

	function mod(n, m) {
		return ((n % m) + m) % m;
	}

	function assign(target, source) {
		for (var key in source) {
			if (Object.prototype.hasOwnProperty.call(source, key)) {
				target[key] = source[key];
			}
		}
		return target;
	}

	function WTSSlider(wrap) {
		this.wrap = wrap;
		this.viewport = wrap.querySelector('.wts-viewport');
		this.track = wrap.querySelector('.wts-track');
		this.panel = wrap.querySelector('.wts-quote-panel');
		this.dotsWrap = wrap.querySelector('.wts-dots');
		this.prevBtn = wrap.querySelector('.wts-prev');
		this.nextBtn = wrap.querySelector('.wts-next');

		if (!this.viewport || !this.track) {
			return;
		}

		this.config = assign(defaults(), this.readConfig());
		this.originals = Array.prototype.slice.call(this.track.children);
		this.count = this.originals.length;
		this.index = 0;
		this.absIndex = 0;
		this.timer = null;
		this.factor = 1.8;
		this.sideMin = 240;
		var gapValue = parseFloat(window.getComputedStyle(wrap).getPropertyValue('--wts-gap'));
		this.gapPx = isNaN(gapValue) ? 24 : gapValue;

		if (!this.count) {
			return;
		}

		this.originals.forEach(function (slide, i) {
			slide.classList.add('wts-c' + ((i % 5) + 1));
			slide.setAttribute('data-index', i);
			slide.setAttribute('role', 'listitem');
		});

		this.buildClones();
		this.absIndex = this.offset;
		this.buildDots();
		this.bindEvents();

		this.wrap.classList.add('wts-no-anim');
		this.render(false);
		var wrap = this.wrap;
		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				wrap.classList.remove('wts-no-anim');
			});
		});

		this.startAutoplay();

		var self = this;
		this.track.addEventListener('transitionend', function (event) {
			if (event.propertyName === 'transform') {
				self.settle();
			}
		});
	}

	WTSSlider.prototype.readConfig = function () {
		var raw = this.viewport.getAttribute('data-wts-config');
		if (!raw) {
			return {};
		}
		try {
			return JSON.parse(raw) || {};
		} catch (e) {
			return {};
		}
	};

	WTSSlider.prototype.buildClones = function () {
		this.offset = 0;

		if (this.config.loop && this.count > 1) {
			var n = Math.min(this.count, 6);
			var before = document.createDocumentFragment();
			var i;

			for (i = this.count - n; i < this.count; i++) {
				before.appendChild(this.originals[i].cloneNode(true));
			}
			this.track.insertBefore(before, this.originals[0]);

			for (i = 0; i < n; i++) {
				this.track.appendChild(this.originals[i].cloneNode(true));
			}
			this.offset = n;
		}

		this.slides = Array.prototype.slice.call(this.track.children);
	};

	WTSSlider.prototype.buildDots = function () {
		if (!this.dotsWrap || !this.config.dots) {
			return;
		}

		var self = this;
		this.dots = [];

		this.originals.forEach(function (slide, i) {
			var dot = document.createElement('button');
			dot.type = 'button';
			dot.className = 'wts-dot';
			dot.setAttribute('role', 'tab');
			dot.setAttribute('aria-label', (i + 1) + ' / ' + self.count);
			dot.addEventListener('click', function () {
				self.goToReal(i);
			});
			self.dotsWrap.appendChild(dot);
			self.dots.push(dot);
		});
	};

	WTSSlider.prototype.bindEvents = function () {
		var self = this;

		if (this.prevBtn) {
			this.prevBtn.addEventListener('click', function () {
				self.prev();
			});
		}
		if (this.nextBtn) {
			this.nextBtn.addEventListener('click', function () {
				self.next();
			});
		}

		this.slides.forEach(function (slide) {
			slide.addEventListener('click', function () {
				if (slide.classList.contains('is-active')) {
					return;
				}
				self.goToReal(parseInt(slide.getAttribute('data-index'), 10) || 0);
			});
		});

		this.viewport.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				self.prev();
			} else if (event.key === 'ArrowRight') {
				event.preventDefault();
				self.next();
			}
		});

		this.bindDrag();

		var resizeTimer = null;
		window.addEventListener('resize', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(function () {
				self.render(false);
			}, 150);
		});
	};

	WTSSlider.prototype.bindDrag = function () {
		var self = this;
		var startX = 0;
		var delta = 0;
		var dragging = false;

		function point(event) {
			if (event.touches && event.touches.length) {
				return event.touches[0].clientX;
			}
			return event.clientX;
		}

		function onDown(event) {
			dragging = true;
			delta = 0;
			startX = point(event);
			self.track.classList.add('is-dragging');
			self.stopAutoplay();
		}

		function onMove(event) {
			if (!dragging) {
				return;
			}
			delta = point(event) - startX;
			self.track.style.transform = 'translate3d(' + (self.currentX + delta) + 'px,0,0)';
		}

		function onUp() {
			if (!dragging) {
				return;
			}
			dragging = false;
			self.track.classList.remove('is-dragging');
			var threshold = Math.max(40, self.viewport.clientWidth * 0.08);
			if (delta <= -threshold) {
				self.next();
			} else if (delta >= threshold) {
				self.prev();
			} else {
				self.render(true);
			}
			self.startAutoplay();
		}

		this.viewport.addEventListener('touchstart', onDown, { passive: true });
		this.viewport.addEventListener('touchmove', onMove, { passive: true });
		this.viewport.addEventListener('touchend', onUp);
		this.viewport.addEventListener('mousedown', function (event) {
			event.preventDefault();
			onDown(event);
			document.addEventListener('mousemove', onMove);
			document.addEventListener('mouseup', function up() {
				document.removeEventListener('mousemove', onMove);
				document.removeEventListener('mouseup', up);
				onUp();
			});
		});
	};

	WTSSlider.prototype.visibleCount = function () {
		var width = window.innerWidth;
		if (width <= this.config.breakpointMobile) {
			return Math.max(1, this.config.mobile);
		}
		if (width <= this.config.breakpointTablet) {
			return Math.max(1, this.config.tablet);
		}
		return Math.max(1, this.config.desktop);
	};

	WTSSlider.prototype.widths = function () {
		var visible = this.visibleCount();
		var base = 100 / (visible - 1 + this.factor);
		return {
			base: base,
			active: base * this.factor
		};
	};

	WTSSlider.prototype.applyWidths = function () {
		var w = this.widths();
		this.wrap.style.setProperty('--wts-slide-w', w.base.toFixed(4) + '%');
		this.wrap.style.setProperty('--wts-slide-w-active', w.active.toFixed(4) + '%');
		this.basePct = w.base;
		this.activePct = w.active;
	};

	/* Final geometry is computed from the known percentages, so it stays
	   correct while the CSS width/height transitions are still running. */
	WTSSlider.prototype.render = function (animate) {
		if (this.absIndex < 0) {
			this.absIndex = 0;
		}
		if (this.absIndex >= this.slides.length) {
			this.absIndex = this.slides.length - 1;
		}

		this.applyWidths();

		var contentW = this.viewport.clientWidth;
		var basePx = (this.basePct / 100) * contentW;
		var activePx = (this.activePct / 100) * contentW;
		var leftPx = this.absIndex * basePx;
		var x = contentW / 2 - (leftPx + activePx / 2);

		this.track.style.transitionDuration = animate === false ? '0ms' : (this.config.speed + 'ms');
		this.track.style.transform = 'translate3d(' + x + 'px,0,0)';
		this.currentX = x;

		var self = this;
		this.slides.forEach(function (slide, i) {
			slide.classList.toggle('is-active', i === self.absIndex);
			slide.setAttribute('tabindex', i === self.absIndex ? '-1' : '0');
		});

		this.updatePanel();
		this.updateDots();
		this.updateArrows();
	};

	WTSSlider.prototype.updatePanel = function () {
		if (!this.panel) {
			return;
		}

		var active = this.slides[this.absIndex];
		var content = active ? active.querySelector('.wts-slide-content') : null;

		this.panel.innerHTML = content ? content.innerHTML : '';

		var contentW = this.viewport.clientWidth;
		var basePx = (this.basePct / 100) * contentW;
		var activePx = (this.activePct / 100) * contentW;
		var tileLeft = this.viewport.offsetLeft + this.absIndex * basePx + (this.gapPx / 2) + this.currentX;
		var tileTop = this.viewport.offsetTop;
		var tileWidth = activePx - this.gapPx;
		var sideLeft = tileLeft + tileWidth + 32;
		var avail = this.wrap.clientWidth - sideLeft - 4;

		if (avail < this.sideMin) {
			this.panel.classList.add('is-below');
			this.panel.style.left = '';
			this.panel.style.top = '';
			this.panel.style.width = '';
		} else {
			this.panel.classList.remove('is-below');
			this.panel.style.left = sideLeft + 'px';
			this.panel.style.top = tileTop + 'px';
			this.panel.style.width = avail + 'px';
		}
	};

	WTSSlider.prototype.updateDots = function () {
		if (!this.dots) {
			return;
		}
		var self = this;
		this.dots.forEach(function (dot, i) {
			var on = i === self.index;
			dot.classList.toggle('is-active', on);
			dot.setAttribute('aria-selected', on ? 'true' : 'false');
		});
	};

	WTSSlider.prototype.updateArrows = function () {
		if (this.config.loop) {
			return;
		}
		if (this.prevBtn) {
			this.prevBtn.disabled = this.index === 0;
		}
		if (this.nextBtn) {
			this.nextBtn.disabled = this.index === this.count - 1;
		}
	};

	/* After a loop clone has scrolled into view, snap back to the real slide. */
	WTSSlider.prototype.settle = function () {
		if (!this.offset) {
			return;
		}
		var shifted = null;

		if (this.absIndex >= this.offset + this.count) {
			shifted = this.absIndex - this.count;
		} else if (this.absIndex < this.offset) {
			shifted = this.absIndex + this.count;
		}

		if (shifted !== null) {
			this.absIndex = shifted;
			this.render(false);
		}
	};

	WTSSlider.prototype.goToReal = function (i, animate) {
		this.index = mod(i, this.count);
		this.absIndex = this.offset + this.index;
		this.render(animate);
		this.restartAutoplay();
	};

	WTSSlider.prototype.next = function () {
		this.goToReal(this.index + 1);
	};

	WTSSlider.prototype.prev = function () {
		this.goToReal(this.index - 1);
	};

	WTSSlider.prototype.startAutoplay = function () {
		var self = this;
		if (!this.config.autoplay || this.count < 2) {
			return;
		}
		this.stopAutoplay();
		this.timer = window.setInterval(function () {
			self.goToReal(self.index + 1);
		}, Math.max(1000, this.config.autoplaySpeed));
	};

	WTSSlider.prototype.stopAutoplay = function () {
		if (this.timer) {
			window.clearInterval(this.timer);
			this.timer = null;
		}
	};

	WTSSlider.prototype.restartAutoplay = function () {
		this.stopAutoplay();
		this.startAutoplay();
	};

	function initAll() {
		var wraps = document.querySelectorAll('.wts-slider-wrap');
		Array.prototype.forEach.call(wraps, function (wrap) {
			if (wrap.getAttribute('data-wts-init')) {
				return;
			}
			wrap.setAttribute('data-wts-init', '1');
			instances.push(new WTSSlider(wrap));
		});
	}

	function refreshAll() {
		instances.forEach(function (instance) {
			if (instance && instance.render) {
				instance.render(false);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	window.WTSSlider = {
		init: initAll,
		refresh: refreshAll,
		instances: instances
	};
}());
