<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'Dashboard' ?> - Yuk Sehat</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASEURL; ?>/assets/css/style.css?v=2.2">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap');
        
        :root {
            --primary-emerald: #059669;
            --primary-emerald-dark: #047857;
            --sidebar-dark: #0b1120;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #f1f5f9;
        }

        body { 
            background-color: #f8fafc; 
            overflow-x: hidden; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
        }
        
        .wrapper { display: flex; width: 100%; min-height: 100vh; align-items: stretch; overflow-x: hidden; }
        
        /* ============================================================== */
        /* MODERN SIDEBAR                                                 */
        /* ============================================================== */
        #sidebar { 
            min-width: 255px; 
            max-width: 255px; 
            width: 255px; 
            background: linear-gradient(180deg, #0b1120 0%, #0f172a 100%); 
            color: #fff; 
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            flex-shrink: 0; 
            border-right: 1px solid var(--sidebar-border);
            z-index: 1050;
        }
        
        .sidebar-header {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--sidebar-border);
        }
        
        .sidebar-brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.2rem;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
            flex-shrink: 0;
        }
        
        .sidebar-brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.3px;
            line-height: 1.1;
        }
        
        .sidebar-brand-subtitle {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.2px;
        }
        
        .sidebar-user-badge {
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 10px;
        }
        
        .sidebar-user-role {
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 0.2px;
        }
        
        .sidebar-user-desc {
            font-size: 11px;
            color: #64748b;
        }
        
        .sidebar-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            flex-shrink: 0;
            display: inline-block;
        }
        
        .sidebar-menu-wrapper::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .sidebar-heading {
            list-style: none;
            padding: 10px 18px 4px 18px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #475569;
        }
        
        #sidebar .nav-link { 
            color: #94a3b8; 
            padding: 9px 14px; 
            margin: 2px 12px;
            border-radius: 10px;
            font-weight: 500; 
            font-size: 13.5px; 
            display: flex; 
            align-items: center; 
            gap: 12px;
            transition: all 0.18s ease; 
        }
        
        #sidebar .nav-link i { 
            width: 20px; 
            text-align: center; 
            font-size: 15px; 
            color: #64748b;
            transition: color 0.18s ease, transform 0.18s ease;
        }
        
        #sidebar .nav-link:hover { 
            color: #f8fafc; 
            background-color: rgba(255, 255, 255, 0.06); 
            transform: translateX(2px);
        }
        
        #sidebar .nav-link:hover i {
            color: #6ee7b7;
        }
        
        #sidebar .nav-link.active { 
            color: #ffffff !important; 
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; 
            border: none !important;
            font-weight: 600; 
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
        }
        
        #sidebar .nav-link.active i {
            color: #ffffff !important;
        }

        /* ============================================================== */
        /* TOPBAR & CONTENT LAYOUT                                        */
        /* ============================================================== */
        #content { 
            width: 100%; 
            min-width: 0; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            overflow-x: hidden; 
            background-color: #f8fafc; 
        }
        
        .topbar { 
            background: rgba(255, 255, 255, 0.88); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0; 
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02); 
            padding: 8px 24px; 
            min-height: 56px; 
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        .topbar-user-chip {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        
        .topbar-user-chip:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .navbar-avatar {
            width: 32px; 
            height: 32px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border-radius: 50%;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: #fff; 
            font-size: 12px; 
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35);
            letter-spacing: 0.5px;
        }
        
        .main-wrapper { 
            padding: 20px 24px 28px 24px; 
            overflow-x: hidden; 
            flex: 1; 
        }

        /* ============================================================== */
        /* MODERN STAT CARDS                                              */
        /* ============================================================== */
        .stat-card { 
            background: #ffffff; 
            border-radius: 16px; 
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03); 
            border: 1px solid #e2e8f0; 
            height: 100%; 
            display: flex; 
            flex-direction: column; 
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s ease; 
        }
        
        .stat-card:hover { 
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.03); 
            border-color: #cbd5e1;
        }
        
        .stat-card-body { 
            padding: 18px 20px 14px 20px; 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
        }
        
        .stat-card-footer { 
            padding: 10px 20px; 
            border-top: 1px solid #f1f5f9; 
            background: #fafbfc; 
            border-bottom-left-radius: 16px; 
            border-bottom-right-radius: 16px; 
            font-size: 12.5px; 
            margin-top: auto; 
        }
        
        .stat-card-footer a { 
            text-decoration: none; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            transition: color 0.15s ease;
        }
        
        .stat-card-footer a i {
            transition: transform 0.2s ease;
        }
        
        .stat-card-footer a:hover i {
            transform: translateX(4px);
        }
        
        .stat-icon { 
            width: 46px; 
            height: 46px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 21px; 
            margin-bottom: 12px; 
            flex-shrink: 0; 
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.06);
        }
        
        /* Stat icon modern soft colored backgrounds */
        .stat-icon.bg-primary { 
            background-color: #eff6ff !important; 
            color: #2563eb !important; 
        }
        .stat-icon.bg-success { 
            background-color: #ecfdf5 !important;  
            color: #059669 !important; 
        }
        .stat-icon.bg-warning { 
            background-color: #fffbeb !important;  
            color: #d97706 !important; 
        }
        .stat-icon.bg-danger { 
            background-color: #fef2f2 !important;   
            color: #dc2626 !important; 
        }
        
        .stat-label { 
            font-size: 11px; 
            font-weight: 700; 
            color: #64748b; 
            text-transform: uppercase; 
            letter-spacing: 0.6px; 
            margin-bottom: 3px; 
        }
        
        .stat-value { 
            font-family: 'Outfit', sans-serif;
            font-size: 2rem; 
            font-weight: 800; 
            color: #0f172a; 
            line-height: 1.1; 
            margin-bottom: 3px; 
        }
        
        .stat-desc { 
            font-size: 12px; 
            color: #94a3b8; 
        }
        
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1040; backdrop-filter: blur(2px); }
        .sidebar-overlay.active { display: block; }
        
        /* Utilities for Medical Notes & Clean Table Layout */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-word;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-word;
        }
        .hasil-pemeriksaan-cell {
            max-width: 280px;
            min-width: 200px;
        }
        .medical-notes-content h6 {
            font-size: 0.95rem;
        }

        @media (max-width: 991.98px) {
            #sidebar { margin-left: -255px; position: fixed; top: 0; bottom: 0; left: 0; z-index: 1050; }
            #sidebar.active { margin-left: 0; box-shadow: 4px 0 25px rgba(0,0,0,0.35); }
            .main-wrapper { padding: 16px; }
            .topbar { padding: 7px 16px; }
        }

        /* ============================================================== */
        /* MODERN FLUID INTERACTIONS & ZERO STIFFNESS (ANTI-KAKU)         */
        /* ============================================================== */
        html {
            scroll-behavior: smooth;
        }

        /* 1. Global Buttons & Click Tactile Micro-Interactions */
        .btn, 
        button:not(.btn-close), 
        input[type="submit"], 
        .dropdown-item,
        .btn-detail-notif {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
            user-select: none;
            -webkit-user-select: none;
        }

        .btn:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn:active, 
        button:not(.btn-close):active, 
        input[type="submit"]:active {
            transform: scale(0.96) translateY(0) !important;
            transition-duration: 0.08s !important;
            filter: brightness(0.96);
        }

        .btn-outline-success:hover, 
        .btn-outline-primary:hover,
        .btn-outline-secondary:hover {
            box-shadow: 0 3px 10px rgba(5, 150, 105, 0.15);
        }

        /* 2. Fluid Dropdown Menu Animations */
        .dropdown-menu {
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            border-radius: 14px !important;
            box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.12), 0 4px 10px -2px rgba(15, 23, 42, 0.04) !important;
            transform-origin: top center;
        }

        .dropdown-menu.show {
            animation: dropdownSlideIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
        }

        @keyframes dropdownSlideIn {
            0% {
                opacity: 0;
                transform: translateY(-8px) scale(0.97);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
        }

        .dropdown-item:hover {
            background-color: #f1f5f9;
            color: #047857;
            transform: translateX(3px);
        }

        .dropdown-item:active {
            transform: scale(0.97) !important;
            background-color: #e2e8f0;
        }

        /* 3. Fluid Modern Modal Animations with Backdrop Blur */
        .modal-backdrop.show {
            backdrop-filter: blur(8px) saturate(140%);
            -webkit-backdrop-filter: blur(8px) saturate(140%);
            background-color: rgba(15, 23, 42, 0.45) !important;
            opacity: 1 !important;
            transition: all 0.25s ease;
        }

        .modal.fade .modal-dialog {
            transform: scale(0.93) translateY(16px);
            opacity: 0;
            transition: transform 0.26s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.26s ease !important;
        }

        .modal.show .modal-dialog {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .modal-content {
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.8) !important;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
            overflow: hidden;
        }

        /* 4. Fluid Card & List-Group Micro-Interactions */
        .card {
            transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease;
        }

        .list-group-item {
            transition: background-color 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
        }

        .list-group-item-action:hover {
            background-color: #f8fafc;
            transform: translateX(2px);
        }

        .list-group-item-action:active {
            transform: scale(0.99) !important;
        }

        /* Table row smooth hover */
        .table tbody tr {
            transition: background-color 0.15s ease, transform 0.15s ease;
        }

        .table-hover > tbody > tr:hover {
            background-color: #f8fafc !important;
        }

        /* 5. Inputs & Form Controls with Soft Emerald Glow */
        .form-control, .form-select {
            transition: border-color 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease;
            border-radius: 10px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3.5px rgba(16, 185, 129, 0.18) !important;
            background-color: #ffffff !important;
        }

        /* 6. Nav Pills & Tabs Fluid Motion */
        .nav-pills .nav-link {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 9999px;
        }

        .nav-pills .nav-link:active {
            transform: scale(0.96);
        }

        /* 7. Sleek Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* 8. Badge pulse animation for live items */
        .badge.bg-danger {
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.35);
        }

        /* 9. Ripple Micro-Interaction */
        .btn, .btn-detail-notif {
            position: relative;
            overflow: hidden;
        }
        .ui-click-ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: uiRippleEffect 0.55s ease-out;
            background-color: rgba(255, 255, 255, 0.35);
            pointer-events: none;
        }
        .btn-outline-success .ui-click-ripple,
        .btn-outline-secondary .ui-click-ripple,
        .btn-outline-primary .ui-click-ripple,
        .btn-light .ui-click-ripple,
        .dropdown-item .ui-click-ripple {
            background-color: rgba(5, 150, 105, 0.16);
        }
        @keyframes uiRippleEffect {
            to {
                transform: scale(3.5);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Component -->
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Navbar Component -->
            <?php require_once __DIR__ . '/../components/navbar.php'; ?>
            
            <div class="main-wrapper">
                <?php SessionHelper::flash(); ?>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-0 fw-bold fs-5 text-dark"><?= $data['title'] ?? 'Dashboard' ?></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0" style="font-size: 12px;">
                                <li class="breadcrumb-item"><a href="<?= BASEURL ?>" class="text-success text-decoration-none"><i class="bi bi-house-door-fill"></i> Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= $data['title'] ?? 'Dashboard' ?></li>
                            </ol>
                        </nav>
                    </div>
                </div>