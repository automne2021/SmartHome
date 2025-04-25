<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'SmartHome Dashboard'; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
    <link rel="stylesheet" href="../src/assets/css/components.css">
    <link rel="stylesheet" href="../src/assets/css/layout.css">
    <link rel="stylesheet" href="../src/assets/css/sidebar.css">
    <link rel="stylesheet" href="../src/assets/css/header.css">
    <link rel="stylesheet" href="../src/assets/css/footer.css">
    <?php if (isset($additionalCss) && is_array($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
            <link rel="stylesheet" href="../src/assets/css/<?php echo $css; ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
</head>
<body>
    <div class="app-container <?php echo isset($sidebarCollapsed) && $sidebarCollapsed ? 'sidebar-collapsed' : ''; ?>">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Header -->
            <?php include 'header.php'; ?>
            
            <!-- Page Content -->
            <div class="page-content">
                <?php include $contentView; ?>
            </div>
            
            <!-- Footer -->
            <?php include 'footer.php'; ?>
        </div>
    </div>
    
    <script src="../src/assets/js/autoSync.js"></script>
    <script src="../src/assets/js/main.js"></script>
    <script src="../src/assets/js/layout.js"></script>
    <?php if (isset($additionalJs) && is_array($additionalJs)): ?>
        <?php foreach ($additionalJs as $js): ?>
            <script src="../src/assets/js/<?php echo $js; ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>