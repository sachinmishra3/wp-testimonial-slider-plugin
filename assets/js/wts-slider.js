/**
 * WP Testimonial Slider - front-end slider.
 *
 * A dependency-free, responsive, center-mode carousel. The active slide is
 * highlighted and the number of visible slides adapts per breakpoint.
 */
( function () {
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
			dots: 1,
			centerMode: 1,
			equalHeight: 1
		};
	}

	function mod( n, m ) {
		return ( ( n % m ) + m ) % m;
	}

	/**
	 * One slider instance.
	 *
	 * @param {HTMLElement} wrap The .wts-slider-wrap element.
	 */
	function WTSSlider( wrap ) {
		this.wrap = wrap;
		this.viewport = wrap.querySelector( '.wts-viewport' );
		this.track = wrap.querySelector( '.wts-track' );

		if ( ! this.viewport || ! this.track ) {
			return;
		}

		this.config = Object.assign( defaults(), this.readConfig() );

		// The real slides, before any loop clones are added.
		this.originals = Array.prototype.slice.call( this.track.children );
		this.count = this.originals.length;

		if ( this.count === 0 ) {
			return;
		}

		this.offset = 0;
		this.slides = this.originals.slice();
		this.visible = this.config.desktop;
		this.itemWidth = 0;
		this.absIndex = 0;
		this.realIndex = 0;
		this.dots = [];
		this.timer = null;
		this.isPointerDown = false;
		this.isDragging = false;
		this.startX = 0;
		this.dragDelta = 0;
		this.didSwipe = false;

		this.arrowPrev = wrap.querySelector( '.wts-prev' );
		this.arrowNext = wrap.querySelector( '.wts-next' );
		this.dotsWrap = wrap.querySelector( '.wts-dots' );

		this.build();
		this.bind();

		instances.push( this );
	}

	WTSSlider.prototype.readConfig = function () {
		var raw = this.viewport.getAttribute( 'data-wts-config' );
		var parsed = {};

		if ( raw ) {
			try {
				parsed = JSON.parse( raw ) || {};
			} catch ( e ) {
				parsed = {};
			}
		}

		return parsed;
	};

	WTSSlider.prototype.build = function () {
		if ( this.config.centerMode ) {
			this.wrap.classList.add( 'wts-center-mode' );
		}

		if ( this.config.equalHeight ) {
			this.wrap.classList.add( 'wts-equal-height' );
		}

		if ( this.config.loop ) {
			this.buildClones();
		}

		this.refresh( true );
		this.renderDots();
		this.goTo( this.offset, false );
		this.startAutoplay();
	};

	/**
	 * Duplicate the slides before and after so the track can loop seamlessly.
	 */
	WTSSlider.prototype.buildClones = function () {
		if ( this.count < 2 ) {
			return;
		}

		var before = document.createDocumentFragment();
		var after = document.createDocumentFragment();
		var i;

		for ( i = 0; i < this.count; i++ ) {
			before.appendChild( this.cloneSlide( this.originals[ i ] ) );
			after.appendChild( this.cloneSlide( this.originals[ i ] ) );
		}

		this.track.insertBefore( before, this.track.firstChild );
		this.track.appendChild( after );

		this.offset = this.count;
		this.slides = Array.prototype.slice.call( this.track.children );
	};

	WTSSlider.prototype.cloneSlide = function ( slide ) {
		var clone = slide.cloneNode( true );
		clone.classList.remove( 'is-active' );
		clone.setAttribute( 'aria-hidden', 'true' );
		clone.removeAttribute( 'tabindex' );
		return clone;
	};

	/**
	 * Recalculate the visible slide count and geometry.
	 *
	 * @param {boolean} skipPosition When true, do not reposition the track.
	 */
	WTSSlider.prototype.refresh = function ( skipPosition ) {
		this.visible = this.computeVisible();
		this.maxIndex = this.count - 1;

		this.viewport.style.setProperty( '--wts-visible', String( this.visible ) );

		var viewportWidth = this.viewport.clientWidth;
		this.itemWidth = viewportWidth / this.visible;

		if ( ! skipPosition ) {
			this.setPosition( false );
		}
	};

	WTSSlider.prototype.computeVisible = function () {
		var width = window.innerWidth;
		var value;

		if ( width <= this.config.breakpointMobile ) {
			value = this.config.mobile;
		} else if ( width <= this.config.breakpointTablet ) {
			value = this.config.tablet;
		} else {
			value = this.config.desktop;
		}

		return Math.max( 1, parseInt( value, 10 ) || 1 );
	};

	/**
	 * Position the track so the active slide is centered (or left aligned).
	 *
	 * @param {boolean} animate Whether to animate the movement.
	 */
	WTSSlider.prototype.setPosition = function ( animate ) {
		var track = this.track;
		var x;

		if ( this.config.centerMode ) {
			x = -( this.absIndex * this.itemWidth ) + ( this.viewport.clientWidth - this.itemWidth ) / 2;
		} else {
			x = -( this.absIndex * this.itemWidth );
		}

		if ( ! animate ) {
			track.classList.add( 'is-dragging' );
		}

		track.style.transform = 'translate3d(' + x + 'px, 0, 0)';

		if ( ! animate ) {
			// Force a reflow so the transition-free frame is committed.
			void track.offsetWidth;
			track.classList.remove( 'is-dragging' );
		}
	};

	WTSSlider.prototype.updateActive = function () {
		var active = this.slides[ this.absIndex ];
		var i;

		for ( i = 0; i < this.slides.length; i++ ) {
			this.slides[ i ].classList.remove( 'is-active' );
		}

		if ( active ) {
			active.classList.add( 'is-active' );
		}

		this.realIndex = mod( this.absIndex - this.offset, this.count );
		this.updateDots();
		this.updateArrows();
	};

	WTSSlider.prototype.updateArrows = function () {
		if ( ! this.config.loop ) {
			if ( this.arrowPrev ) {
				this.arrowPrev.disabled = this.absIndex <= 0;
			}
			if ( this.arrowNext ) {
				this.arrowNext.disabled = this.absIndex >= this.count - 1;
			}
		}
	};

	/**
	 * Move to an absolute index.
	 *
	 * @param {number}  index   Absolute index within this.slides.
	 * @param {boolean} animate Whether to animate.
	 * @param {boolean} wrap    Whether to wrap/clamp.
	 */
	WTSSlider.prototype.goTo = function ( index, animate, wrap ) {
		var loop = this.config.loop && this.offset > 0;

		if ( false !== wrap ) {
			if ( loop ) {
				// Allow free movement into the clone regions; normalise after.
			} else {
				index = Math.max( 0, Math.min( this.count - 1, index ) );
			}
		}

		this.absIndex = index;
		this.setPosition( animate );
		this.updateActive();
	};

	WTSSlider.prototype.goToReal = function ( realIndex, animate ) {
		var base;

		if ( this.offset > 0 ) {
			base = this.offset + realIndex;

			// Pick the nearest matching index so the track never scrolls far.
			var candidates = [ base, base - this.count, base + this.count ];
			var best = base;
			var bestDist = Infinity;
			var i;

			for ( i = 0; i < candidates.length; i++ ) {
				var dist = Math.abs( candidates[ i ] - this.absIndex );

				if ( dist < bestDist ) {
					bestDist = dist;
					best = candidates[ i ];
				}
			}

			this.goTo( best, animate );
		} else {
			this.goTo( realIndex, animate );
		}
	};

	WTSSlider.prototype.next = function () {
		if ( ! this.config.loop && this.absIndex >= this.count - 1 ) {
			return;
		}

		this.goTo( this.absIndex + 1, true );
	};

	WTSSlider.prototype.prev = function () {
		if ( ! this.config.loop && this.absIndex <= 0 ) {
			return;
		}

		this.goTo( this.absIndex - 1, true );
	};

	/**
	 * After an animated step, snap back into the middle copy when the track
	 * has entered a clone region.
	 */
	WTSSlider.prototype.normalize = function () {
		if ( ! this.config.loop || this.offset === 0 ) {
			return;
		}

		if ( this.absIndex >= this.offset + this.count ) {
			this.absIndex -= this.count;
			this.setPosition( false );
			this.updateActive();
		} else if ( this.absIndex < this.offset ) {
			this.absIndex += this.count;
			this.setPosition( false );
			this.updateActive();
		}
	};

	WTSSlider.prototype.renderDots = function () {
		if ( ! this.dotsWrap ) {
			return;
		}

		this.dotsWrap.innerHTML = '';

		if ( ! this.config.dots || this.count < 2 ) {
			this.dotsWrap.style.display = 'none';
			return;
		}

		var self = this;

		for ( var i = 0; i < this.count; i++ ) {
			( function ( realIndex ) {
				var dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.className = 'wts-dot';
				dot.setAttribute( 'role', 'tab' );
				dot.setAttribute( 'aria-label', ( realIndex + 1 ) + ' / ' + self.count );

				dot.addEventListener( 'click', function () {
					self.pauseAutoplay();
					self.goToReal( realIndex, true );
					self.resumeAutoplay();
				} );

				self.dotsWrap.appendChild( dot );
				self.dots.push( dot );
			}( i ) );
		}
	};

	WTSSlider.prototype.updateDots = function () {
		for ( var i = 0; i < this.dots.length; i++ ) {
			var on = i === this.realIndex;
			this.dots[ i ].classList.toggle( 'is-active', on );
			this.dots[ i ].setAttribute( 'aria-selected', on ? 'true' : 'false' );
		}
	};

	WTSSlider.prototype.startAutoplay = function () {
		if ( ! this.config.autoplay || this.count < 2 || this.config.autoplaySpeed <= 0 ) {
			return;
		}

		var self = this;
		this.pauseAutoplay();
		this.timer = window.setInterval( function () {
			if ( document.hidden ) {
				return;
			}
			self.next();
		}, this.config.autoplaySpeed );
	};

	WTSSlider.prototype.pauseAutoplay = function () {
		if ( this.timer ) {
			window.clearInterval( this.timer );
			this.timer = null;
		}
	};

	WTSSlider.prototype.resumeAutoplay = function () {
		if ( this.config.autoplay && this.count >= 2 ) {
			this.startAutoplay();
		}
	};

	WTSSlider.prototype.bind = function () {
		var self = this;

		if ( this.arrowNext ) {
			this.arrowNext.addEventListener( 'click', function () {
				self.pauseAutoplay();
				self.next();
				self.resumeAutoplay();
			} );
		}

		if ( this.arrowPrev ) {
			this.arrowPrev.addEventListener( 'click', function () {
				self.pauseAutoplay();
				self.prev();
				self.resumeAutoplay();
			} );
		}

		this.track.addEventListener( 'transitionend', function ( event ) {
			if ( event.target === self.track && event.propertyName === 'transform' ) {
				self.normalize();
			}
		} );

		// Click a slide to make it active.
		this.track.addEventListener( 'click', function ( event ) {
			if ( self.didSwipe ) {
				return;
			}

			var slide = event.target.closest ? event.target.closest( '.wts-slide' ) : null;

			if ( ! slide ) {
				return;
			}

			var index = self.slides.indexOf( slide );

			if ( index === -1 || index === self.absIndex ) {
				return;
			}

			self.pauseAutoplay();
			self.goTo( index, true );
			self.resumeAutoplay();
		} );

		// Keyboard support.
		this.track.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'ArrowLeft' ) {
				event.preventDefault();
				self.prev();
			} else if ( event.key === 'ArrowRight' ) {
				event.preventDefault();
				self.next();
			}
		} );

		// Pause while the user interacts.
		this.wrap.addEventListener( 'mouseenter', function () {
			self.pauseAutoplay();
		} );
		this.wrap.addEventListener( 'mouseleave', function () {
			self.resumeAutoplay();
		} );
		this.wrap.addEventListener( 'focusin', function () {
			self.pauseAutoplay();
		} );
		this.wrap.addEventListener( 'focusout', function () {
			self.resumeAutoplay();
		} );

		this.bindPointer();

		var resizeTimer = null;
		window.addEventListener( 'resize', function () {
			if ( resizeTimer ) {
				window.clearTimeout( resizeTimer );
			}
			resizeTimer = window.setTimeout( function () {
				self.refresh( true );
				// Keep the active slide centred after a breakpoint change.
				var real = self.realIndex;
				self.goToReal( real, false );
			}, 150 );
		} );
	};

	WTSSlider.prototype.bindPointer = function () {
		var self = this;

		this.track.addEventListener( 'pointerdown', function ( event ) {
			if ( event.pointerType === 'mouse' && event.button !== 0 ) {
				return;
			}

			self.isPointerDown = true;
			self.isDragging = false;
			self.didSwipe = false;
			self.startX = event.clientX;
			self.dragDelta = 0;
			self.track.classList.add( 'is-dragging' );
			self.pauseAutoplay();
		} );

		this.track.addEventListener( 'pointermove', function ( event ) {
			if ( ! self.isPointerDown ) {
				return;
			}

			self.dragDelta = event.clientX - self.startX;

			if ( Math.abs( self.dragDelta ) > 6 ) {
				self.isDragging = true;
			}

			if ( self.isDragging ) {
				event.preventDefault();
				self.setPosition( false );
				self.track.style.transform = 'translate3d(' + self.currentDragX() + 'px, 0, 0)';
			}
		} );

		var end = function () {
			if ( ! self.isPointerDown ) {
				return;
			}

			self.isPointerDown = false;
			self.track.classList.remove( 'is-dragging' );

			if ( self.isDragging ) {
				var threshold = Math.min( 80, self.itemWidth * 0.35 );

				if ( self.dragDelta <= -threshold ) {
					self.didSwipe = true;
					self.next();
				} else if ( self.dragDelta >= threshold ) {
					self.didSwipe = true;
					self.prev();
				} else {
					self.setPosition( true );
				}

				// Reset the swipe flag after the click event has a chance to fire.
				window.setTimeout( function () {
					self.didSwipe = false;
				}, 0 );
			}

			self.resumeAutoplay();
		};

		this.track.addEventListener( 'pointerup', end );
		this.track.addEventListener( 'pointercancel', end );
		this.track.addEventListener( 'pointerleave', function () {
			if ( self.isPointerDown ) {
				end();
			}
		} );
	};

	WTSSlider.prototype.currentDragX = function () {
		var base;

		if ( this.config.centerMode ) {
			base = -( this.absIndex * this.itemWidth ) + ( this.viewport.clientWidth - this.itemWidth ) / 2;
		} else {
			base = -( this.absIndex * this.itemWidth );
		}

		return base + this.dragDelta;
	};

	function initAll() {
		var nodes = document.querySelectorAll( '.wts-viewport' );

		Array.prototype.forEach.call( nodes, function ( node ) {
			var wrap = node.closest ? node.closest( '.wts-slider-wrap' ) : null;

			if ( wrap && ! wrap.dataset.wtsReady ) {
				wrap.dataset.wtsReady = '1';
				new WTSSlider( wrap );
			}
		} );
	}

	function refreshAll() {
		instances.forEach( function ( instance ) {
			instance.refresh( true );
			instance.goToReal( instance.realIndex, false );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	// Recalculate once images have loaded so widths are accurate.
	window.addEventListener( 'load', refreshAll );

	window.WTSSlider = { init: initAll, refresh: refreshAll, instances: instances };
}() );
