<script>
(function(){
    var btn  = document.getElementById('menu-toggle');
    var side = document.getElementById('sidebar');
    var main = document.getElementById('main-container');
    if (btn && side && main) {
        btn.addEventListener('click', function() {
            side.classList.toggle('collapsed');
            main.classList.toggle('expanded');
            side.classList.toggle('active');
        });
    }
})();
<?= $extra_js ?? '' ?>
</script>
</body>
</html>
