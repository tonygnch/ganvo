{{--
 | Color-mode boot — MUST sit in <head> before the theme's <style> so the
 | visitor's saved mode applies at first paint (no flash). Reads the same
 | localStorage key the kit's toggle writes. Native mode = no attribute.
--}}
<script>
    (function () {
        try {
            var m = localStorage.getItem('gv-mode');
            if (m) document.documentElement.dataset.mode = m;
        } catch (e) { /* private mode etc. — native theme mode stands */ }
    })();
</script>
