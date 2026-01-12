</main>

<?php 
// Get base path for scripts
$basePath = isset($basePath) ? $basePath : '';
if (strpos($_SERVER['REQUEST_URI'], '/user/') !== false) {
    $basePath = '..';
} elseif (strpos($_SERVER['REQUEST_URI'], '/monitor/') !== false) {
    $basePath = '..';
} elseif (strpos($_SERVER['REQUEST_URI'], '/provider/') !== false) {
    $basePath = '..';
}
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Chart.js Zoom Plugin (for ECG) -->
<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>

<!-- Casana Scripts -->
<script src="<?php echo $basePath; ?>/assets/js/theme.js"></script>
<script src="<?php echo $basePath; ?>/assets/js/api.js"></script>
<script src="<?php echo $basePath; ?>/assets/js/charts.js"></script>

<?php if (isset($appName) && $appName === 'provider'): ?>
<!-- Provider-specific scripts -->
<script src="<?php echo $basePath; ?>/assets/js/provider.js"></script>
<?php endif; ?>

<?php if (isset($additionalScripts)) echo $additionalScripts; ?>

</body>
</html>
