(function () {
	var secs = [].slice.call(document.querySelectorAll('.mn-sec'));
	var jump = document.getElementById('mn-jump');
	var mq   = window.matchMedia('(max-width:900px)');
	var hdr  = document.querySelector('.site-header');

	function measure() {
		var h = hdr ? Math.round(hdr.getBoundingClientRect().height) : 100;
		document.documentElement.style.setProperty('--mn-hdr', h + 'px');
		if (jump) {
			document.documentElement.style.setProperty('--mn-jump-h', Math.round(jump.getBoundingClientRect().height) + 'px');
		}
	}

	if (secs.length) {
		measure();
		window.addEventListener('resize', measure);
		if (hdr && 'ResizeObserver' in window) new ResizeObserver(measure).observe(hdr);

		secs.forEach(function (s) {
			var body = s.querySelector('.mn-sec-body');
			if (!body) return;
			[].slice.call(body.childNodes).forEach(function (n) {
				if (n.nodeType !== 3 || !n.textContent.trim()) return;
				var p = document.createElement('p');
				p.textContent = n.textContent.trim();
				body.replaceChild(p, n);
			});
		});

		if (jump) secs.forEach(function (s) {
			var a = document.createElement('a');
			a.href = '#' + s.id;
			a.textContent = s.getAttribute('data-title') || s.id;
			jump.appendChild(a);

			[].forEach.call(s.querySelectorAll('.mn-sub'), function (sub) {
				function label(node) {
					return (node.getAttribute('data-title') ||
						(node.firstChild && node.firstChild.nodeType === 3 ? node.firstChild.textContent : node.textContent) || '').trim();
				}
				if (!sub.id) {
					sub.id = label(sub).toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
				}
				if (!sub.id) return;

				var b = document.createElement('a');
				b.href = '#' + sub.id;
				b.className = 'is-sub';
				b.textContent = label(sub);
				jump.appendChild(b);
			});
		});

		if (jump && 'IntersectionObserver' in window) {
			var links = [].slice.call(jump.querySelectorAll('a'));

			function centreActive() {
				var a = null;
				for (var i = 0; i < links.length; i++) {
					if (links[i].classList.contains('is-active')) { a = links[i]; break; }
				}
				if (!a) return;
				if (jump.scrollWidth > jump.clientWidth + 1) {
					jump.scrollTo({ left: a.offsetLeft - (jump.clientWidth - a.offsetWidth) / 2, behavior: 'smooth' });
				} else if (jump.scrollHeight > jump.clientHeight + 1) {
					jump.scrollTo({ top: a.offsetTop - (jump.clientHeight - a.offsetHeight) / 2, behavior: 'smooth' });
				}
			}

			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (e) {
					if (!e.isIntersecting) return;
					var sec = e.target.classList.contains('mn-sub') ? e.target.closest('.mn-sec') : e.target;
					links.forEach(function (a) {
						var href = a.getAttribute('href');
						a.classList.toggle('is-active', href === '#' + e.target.id || (sec && href === '#' + sec.id));
					});
					centreActive();
				});
			}, { rootMargin: '-20% 0px -70% 0px' });

			var watch = secs.slice();
			secs.forEach(function (s) {
				[].forEach.call(s.querySelectorAll('.mn-sub[id]'), function (sub) { watch.push(sub); });
			});
			watch.forEach(function (s) { io.observe(s); });
		}

		function sync() {
			secs.forEach(function (s) {
				s.classList.toggle('is-collapsed', mq.matches && s.getAttribute('data-open') !== '1');
			});
		}
		sync();
		if (mq.addEventListener) mq.addEventListener('change', sync); else mq.addListener(sync);

		secs.forEach(function (s) {
			var b = s.querySelector('.mn-h-btn');
			if (!b) return;
			b.addEventListener('click', function () {
				if (!mq.matches) return;
				s.classList.toggle('is-collapsed');
			});
		});
	}

	if (window.jQuery && jQuery.fancybox) {
		jQuery('.mn-zoom').fancybox({ loop: false, buttons: ['close'] });
	}
})();
