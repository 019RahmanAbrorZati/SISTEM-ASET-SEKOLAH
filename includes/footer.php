            </main>
        </div>
    </div>
    <script>
    (function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var glass = document.getElementById('navGlass');
        var list = document.querySelector('.app-nav-list');

        function placeGlass(el, instant) {
            if (!glass || !list || !el) return;
            var listRect = list.getBoundingClientRect();
            var r = el.getBoundingClientRect();
            var x = Math.round(r.left - listRect.left);
            var y = Math.round(r.top - listRect.top);
            var w = Math.round(r.width);
            var h = Math.round(r.height);
            if (instant) glass.style.transition = 'none';
            glass.style.width = w + 'px';
            glass.style.height = h + 'px';
            glass.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
            glass.classList.add('is-ready');
            if (instant) {
                glass.offsetHeight;
                glass.style.transition = '';
            }
        }

        var active = document.querySelector('.nav-link-active');
        if (active) placeGlass(active, true);

        window.addEventListener('resize', function () {
            var current = document.querySelector('.nav-link.is-going') || document.querySelector('.nav-link-active');
            if (current) placeGlass(current, true);
        });

        function go(href, link) {
            if (reduce) {
                window.location.href = href;
                return;
            }
            document.body.classList.add('is-leaving');
            if (link) {
                link.classList.add('is-going');
                placeGlass(link);
            }
            window.setTimeout(function () {
                window.location.href = href;
            }, 320);
        }

        document.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('mouseenter', function () {
                placeGlass(link);
            });
            link.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                var href = link.href;
                if (!href || link.target === '_blank') return;
                if (href === window.location.href) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                go(href, link);
            });
        });

        if (list) {
            list.addEventListener('mouseleave', function () {
                var current = document.querySelector('.nav-link.is-going') || document.querySelector('.nav-link-active');
                if (current) placeGlass(current);
            });
        }
    })();
    </script>
    <script src="<?php echo e(url('assets/js/theme.js')); ?>"></script>
</body>
</html>
