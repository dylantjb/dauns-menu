(function () {
	var secs = [].slice.call(document.querySelectorAll('.mn-sec'));
	var jump = document.getElementById('mn-jump');

	if (secs.length) {
		if (jump) document.documentElement.style.setProperty('--mn-jump-h', Math.round(jump.getBoundingClientRect().height) + 'px');

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
	}

	if (window.jQuery && jQuery.fancybox) {
		jQuery('.mn-zoom').fancybox({ loop: false, buttons: ['close'] });
	}
})();
