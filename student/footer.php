</div> <!-- End of container-fluid -->
    </div> <!-- End of page-content-wrapper -->
</div> <!-- End of wrapper -->

<!-- JavaScript -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    // Menu Toggle Logic for Sidebar
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");

    toggleButton.onclick = function () {
        el.classList.toggle("toggled");
    };
</script>
</body>
</html>