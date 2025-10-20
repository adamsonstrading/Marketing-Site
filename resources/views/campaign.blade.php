<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Email Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #1a1a1a;
            --secondary-bg: #2d2d2d;
            --card-bg: #333333;
            --accent-color: #00d4ff;
            --accent-gradient: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            --success-color: #00ff88;
            --warning-color: #ffaa00;
            --danger-color: #ff4444;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --border-color: #404040;
        }

        /* Light theme variables */
        [data-theme="light"] {
            --primary-bg: #f8f9fa;
            --secondary-bg: #ffffff;
            --card-bg: #ffffff;
            --accent-color: #007bff;
            --accent-gradient: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-bg);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--secondary-bg);
            z-index: 1000;
            transition: all 0.3s ease;
            border-right: 1px solid var(--border-color);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--accent-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .nav-section {
            padding: 1rem 0;
        }

        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--accent-gradient);
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .theme-toggle-icons {
            display: flex;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .theme-toggle-icons i {
            transition: opacity 0.3s ease;
        }

        .theme-toggle-icons .fa-sun {
            opacity: 0.5;
        }

        .theme-toggle-icons .fa-moon {
            opacity: 1;
        }

        [data-theme="light"] .theme-toggle-icons .fa-sun {
            opacity: 1;
        }

        [data-theme="light"] .theme-toggle-icons .fa-moon {
            opacity: 0.5;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: var(--accent-color);
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle-switch.active::after {
            transform: translateX(26px);
        }

        .copyright {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: var(--primary-bg);
        }

        /* Header */
        .header {
            background: var(--secondary-bg);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 25px;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .date-time {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            background: var(--card-bg);
            color: var(--accent-color);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .user-profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            transform: scale(1.1);
        }

        /* Dropdown menu styling */
        .dropdown-menu {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 0.5rem 0;
        }

        .dropdown-item {
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--secondary-bg);
            color: var(--accent-color);
        }

        .dropdown-header {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
        }

        .dropdown-divider {
            border-color: var(--border-color);
            margin: 0.5rem 0;
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Cards */
        .modern-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modern-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gradient);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--accent-color);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control, .form-select {
            background: var(--secondary-bg) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px;
            color: var(--text-primary) !important;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none !important;
            border-color: var(--accent-color) !important;
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1) !important;
            background: var(--card-bg) !important;
            color: var(--text-primary) !important;
        }

        .form-control::placeholder {
            color: var(--text-secondary) !important;
        }

        /* Force text color for all input types */
        input[type="text"], 
        input[type="email"], 
        input[type="password"], 
        input[type="search"], 
        textarea, 
        select {
            color: var(--text-primary) !important;
            background-color: var(--secondary-bg) !important;
        }

        input[type="text"]:focus, 
        input[type="email"]:focus, 
        input[type="password"]:focus, 
        input[type="search"]:focus, 
        textarea:focus, 
        select:focus {
            color: var(--text-primary) !important;
            background-color: var(--card-bg) !important;
        }

        /* Override Bootstrap defaults */
        .form-control:not(:focus) {
            color: var(--text-primary) !important;
        }

        /* Ensure all text in forms is visible */
        .form-control, 
        .form-select, 
        .form-control:focus, 
        .form-select:focus,
        .form-control:not(:focus),
        .form-select:not(:focus) {
            color: var(--text-primary) !important;
        }

        /* File input styling */
        input[type="file"] {
            color: var(--text-primary) !important;
            background-color: var(--secondary-bg) !important;
        }

        input[type="file"]:focus {
            color: var(--text-primary) !important;
            background-color: var(--card-bg) !important;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            border-radius: 10px 10px 0 0;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background: var(--card-bg);
            color: var(--text-primary);
            transform: none;
        }

        .nav-tabs .nav-link.active {
            background: var(--accent-gradient);
            color: white !important;
            transform: none;
        }

        /* Ensure tab navigation text is visible */
        .nav-tabs .nav-link {
            color: var(--text-secondary) !important;
        }

        .nav-tabs .nav-link:hover {
            color: var(--text-primary) !important;
        }

        .nav-tabs .nav-link.active {
            color: white !important;
        }

        /* Buttons */
        .btn-primary {
            background: var(--accent-gradient);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.3);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-secondary {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--secondary-bg);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        /* Status Cards */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .status-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gradient);
        }

        .status-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .status-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Progress Ring */
        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
        }

        .progress-ring svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
        }

        .progress-ring-bg {
            stroke: var(--border-color);
        }

        .progress-ring-progress {
            stroke: url(#gradient);
            transition: stroke-dasharray 0.5s ease;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Tables */
        .table {
            color: var(--text-primary);
            background: transparent;
        }

        .table th {
            border-color: var(--border-color);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            border-color: var(--border-color);
            padding: 1rem 0.75rem;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Badges */
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
        }

        .badge-success {
            background: var(--success-color);
            color: #000;
        }

        .badge-warning {
            background: var(--warning-color);
            color: #000;
        }

        .badge-danger {
            background: var(--danger-color);
            color: white;
        }

        .badge-info {
            background: var(--accent-color);
            color: white;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border-left-color: var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(255, 68, 68, 0.1);
            border-left-color: var(--danger-color);
            color: var(--danger-color);
        }

        .alert-warning {
            background: rgba(255, 170, 0, 0.1);
            border-left-color: var(--warning-color);
            color: var(--warning-color);
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            border: 3px solid var(--border-color);
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                padding: 1rem;
            }
            
            .content-area {
                padding: 1rem;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--secondary-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .modern-card {
            animation: slideInLeft 0.8s ease-out;
        }

        /* Glow effects */
        .status-card:hover::before {
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        /* Pulse animation for active elements */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 212, 255, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(0, 212, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 212, 255, 0);
            }
        }

        .nav-link.active {
            animation: pulse 2s infinite;
        }

        /* Floating animation for logo */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .logo-icon {
            animation: float 3s ease-in-out infinite;
        }

        /* Light theme specific adjustments */
        [data-theme="light"] .modern-card {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        [data-theme="light"] .modern-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        [data-theme="light"] .status-card:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        /* Live Campaign Dashboard Styles */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .campaign-info {
            background: var(--secondary-bg);
            border-radius: 6px;
            padding: 0.75rem;
        }

        .activity-feed {
            background: var(--secondary-bg);
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid var(--border-color);
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.8rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        [data-theme="light"] .btn-primary:hover {
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.3);
        }

        [data-theme="light"] .nav-link:hover {
            background: rgba(0, 123, 255, 0.1);
        }

        [data-theme="light"] .nav-link.active {
            background: var(--accent-gradient);
        }

        /* Additional text visibility fixes */
        * {
            color: inherit;
        }

        /* Override any Bootstrap text colors */
        .text-dark, .text-black {
            color: var(--text-primary) !important;
        }

        /* Ensure all form text is visible */
        .form-control, 
        .form-select, 
        .form-control-sm, 
        .form-control-lg,
        .form-control:focus,
        .form-select:focus,
        .form-control:active,
        .form-select:active {
            color: var(--text-primary) !important;
        }

        /* Override any inherited colors */
        input, textarea, select {
            color: var(--text-primary) !important;
        }

        /* Specific fixes for common issues */
        .form-control::-webkit-input-placeholder {
            color: var(--text-secondary) !important;
        }

        .form-control::-moz-placeholder {
            color: var(--text-secondary) !important;
        }

        .form-control:-ms-input-placeholder {
            color: var(--text-secondary) !important;
        }

        .form-control:-moz-placeholder {
            color: var(--text-secondary) !important;
        }

        /* Form helper text and descriptions */
        .form-text {
            color: var(--text-secondary) !important;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* All text elements in forms */
        .form-label,
        .form-text,
        .form-help,
        .help-text,
        .text-muted,
        .text-secondary,
        small,
        .small {
            color: var(--text-secondary) !important;
        }

        /* Tab content text */
        .tab-content {
            color: var(--text-primary) !important;
        }

        .tab-content h6 {
            color: var(--text-primary) !important;
        }

        /* Alert text */
        .alert {
            color: inherit !important;
        }

        .alert-info {
            color: var(--accent-color) !important;
        }

        /* Table text in CSV preview */
        .table th,
        .table td {
            color: var(--text-primary) !important;
        }

        /* Links in forms */
        .form-text a {
            color: var(--accent-color) !important;
            text-decoration: none;
        }

        .form-text a:hover {
            color: var(--text-primary) !important;
            text-decoration: underline;
        }

        /* Ensure all text in campaign form is visible */
        #campaign-tab * {
            color: inherit;
        }

        #campaign-tab .form-label {
            color: var(--text-primary) !important;
        }

        #campaign-tab .form-text {
            color: var(--text-secondary) !important;
        }

        #campaign-tab h6 {
            color: var(--text-primary) !important;
        }

        #campaign-tab small {
            color: var(--text-secondary) !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                Email Agent
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-item">
                <a href="#" class="nav-link active" onclick="showTab('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showTab('campaign')">
                    <i class="fas fa-paper-plane"></i>
                    Campaign
                </a>
            </div>
        </div>
        
        <div class="sidebar-footer">
            <div class="theme-toggle">
                <div class="theme-toggle-icons">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                </div>
                <div class="toggle-switch active" onclick="toggleTheme()"></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search campaigns, recipients...">
            </div>
            <div class="header-right">
                <div class="user-profile dropdown">
                    <div class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->name[0] ?? 'U' }}
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ Auth::user()->name ?? 'User' }}</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="fas fa-user me-2"></i>Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <h1 class="page-title">Email Agent</h1>
            <p class="page-subtitle">Create and manage your email campaigns with ease</p>

            <!-- Dashboard Tab -->
            <div id="dashboard-tab" class="tab-content">
                <div class="row">
                    <!-- Campaign Statistics -->
                    <div class="col-lg-8">
                        <div class="modern-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line"></i>
                                    Campaign Statistics
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Status Metrics -->
                                <div class="status-grid">
                                    <div class="status-card">
                                        <div class="status-number" id="totalCount">0</div>
                                        <div class="status-label">Total Campaigns</div>
                                    </div>
                                    <div class="status-card">
                                        <div class="status-number" id="sentCount">0</div>
                                        <div class="status-label">Total Recipients</div>
                                    </div>
                                    <div class="status-card">
                                        <div class="status-number" id="pendingCount">0</div>
                                        <div class="status-label">In Progress</div>
                                    </div>
                                    <div class="status-card">
                                        <div class="status-number" id="failedCount">0</div>
                                        <div class="status-label">Failed</div>
                                    </div>
                                </div>

                                <!-- Progress Ring -->
                                <div class="progress-ring">
                                    <svg>
                                        <defs>
                                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#00d4ff;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#0099cc;stop-opacity:1" />
                                            </linearGradient>
                                        </defs>
                                        <circle class="progress-ring-circle progress-ring-bg" cx="60" cy="60" r="52"></circle>
                                        <circle class="progress-ring-circle progress-ring-progress" cx="60" cy="60" r="52" 
                                                stroke-dasharray="326.73" stroke-dashoffset="326.73" id="progressRing"></circle>
                                    </svg>
                                    <div class="progress-text" id="progressPercent">0%</div>
                                </div>

                                <div class="mb-3 text-center">
                                    <small class="text-muted">Overall Progress: <span id="campaignStatus" class="badge bg-secondary">No Active Campaigns</span></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-4">
                        <div class="modern-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-clock"></i>
                                    Recent Activity
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="recipient-table">
                                    <div id="recentRecipients">
                                        <small class="text-muted">No recent activity</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Tab -->
            <div id="campaign-tab" class="tab-content" style="display: none;">
                <div class="row">
                    <!-- Campaign Form -->
                    <div class="col-lg-8">
                        <div class="modern-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-plus-circle"></i>
                                    Create New Campaign
                                </h3>
                            </div>
                            <div class="card-body">
                                <form id="campaignForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="campaignName" class="form-label">Campaign Name</label>
                                                <input type="text" class="form-control" id="campaignName" name="name" required placeholder="Enter campaign name">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="smtpConfigSelect" class="form-label">SMTP Configuration</label>
                                                <select class="form-select" id="smtpConfigSelect" name="smtp_configuration_id" required>
                                                    <option value="">Loading SMTP configurations...</option>
                                                </select>
                                                <div class="form-text">Choose which email service to use for sending</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="senderSelect" class="form-label">Sender</label>
                                                <select class="form-select" id="senderSelect" name="sender_id" required>
                                                    <option value="">Loading senders...</option>
                                                </select>
                                                <div class="form-text">Choose the sender for this campaign</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required placeholder="Enter email subject">
                                    </div>

                                    <div class="form-group">
                                        <label for="message" class="form-label">Message (HTML allowed)</label>
                                        <textarea class="form-control" id="message" name="body" rows="6" required placeholder="Enter your email message..."></textarea>
                                        <div class="form-text">You can use HTML tags for formatting.</div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Recipients</label>
                                        
                                        <!-- Tab Navigation -->
                                        <ul class="nav nav-tabs" id="recipientTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                                                    <i class="fas fa-keyboard"></i> Manual Entry
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="csv-tab" data-bs-toggle="tab" data-bs-target="#csv" type="button" role="tab">
                                                    <i class="fas fa-file-excel"></i> File Upload
                                                </button>
                                            </li>
                                        </ul>
                                        
                                        <!-- Tab Content -->
                                        <div class="tab-content" id="recipientTabContent">
                                            <!-- Manual Entry Tab -->
                                            <div class="tab-pane fade show active" id="manual" role="tabpanel">
                                                <textarea class="form-control mt-3" id="recipients" name="recipients" rows="4" 
                                                    placeholder="Enter email addresses, one per line&#10;example@domain.com&#10;another@domain.com"></textarea>
                                                <div class="form-text">One email address per line. Maximum 500 recipients.</div>
                                            </div>
                                            
                                            <!-- CSV Upload Tab -->
                                            <div class="tab-pane fade" id="csv" role="tabpanel">
                                                <div class="mt-3">
                                                    <div class="form-group">
                                                        <label for="csvFile" class="form-label">Upload File</label>
                                                        <input type="file" class="form-control" id="csvFile" accept=".csv,.xls,.xlsx" />
                                                        <div class="form-text">
                                                            Upload CSV file with email addresses in the first column. 
                                                            For Excel files (.xls/.xlsx), please convert to CSV format first.
                                                            Maximum 500 recipients. <a href="#" id="downloadSample">Download sample CSV</a>
                                                        </div>
                                                    </div>
                                                    
                                                    <div id="csvPreview" class="mt-3" style="display: none;">
                                                        <h6>CSV Preview:</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Email</th>
                                                                        <th>Name (Optional)</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="csvPreviewBody">
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <strong>Found <span id="csvCount">0</span> email addresses</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <i class="fas fa-paper-plane"></i>
                                            Create Campaign & Start Sending
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Live Campaign Dashboard -->
                        <div class="card mt-4" id="liveCampaignDashboard" style="display: none;">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line text-primary"></i>
                                    Live Campaign Dashboard
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Campaign Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="campaign-info">
                                            <h6 class="text-muted mb-1">Campaign Name</h6>
                                            <p class="mb-0" id="liveCampaignName">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="campaign-info">
                                            <h6 class="text-muted mb-1">Status</h6>
                                            <span id="liveCampaignStatus" class="badge bg-secondary">Unknown</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Live Statistics -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-card text-center">
                                            <div class="stat-number text-primary" id="liveTotalRecipients">0</div>
                                            <div class="stat-label">Total Recipients</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card text-center">
                                            <div class="stat-number text-success" id="liveSentCount">0</div>
                                            <div class="stat-label">Sent</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card text-center">
                                            <div class="stat-number text-warning" id="livePendingCount">0</div>
                                            <div class="stat-label">Pending</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card text-center">
                                            <div class="stat-number text-danger" id="liveFailedCount">0</div>
                                            <div class="stat-label">Failed</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Progress</span>
                                        <span class="text-muted" id="liveProgressPercent">0%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-gradient" id="liveProgressBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <!-- Live Activity Feed -->
                                <div class="mt-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-history"></i> Live Activity Feed
                                    </h6>
                                    <div class="activity-feed" id="liveActivityFeed" style="max-height: 200px; overflow-y: auto;">
                                        <div class="text-muted text-center py-3">
                                            <i class="fas fa-spinner fa-spin"></i> Waiting for campaign to start...
                                        </div>
                                    </div>
                                </div>

                                <!-- Control Buttons -->
                                <div class="mt-4 d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm" id="refreshLiveStats">
                                        <i class="fas fa-sync-alt"></i> Refresh Stats
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" id="pauseCampaign" style="display: none;">
                                        <i class="fas fa-pause"></i> Pause Campaign
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" id="stopCampaign" style="display: none;">
                                        <i class="fas fa-stop"></i> Stop Campaign
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Dashboard -->
                    <div class="col-lg-4">
                        <div class="modern-card" id="statusCard" style="display: none;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line"></i>
                                    Campaign Status
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Status Metrics -->
                                <div class="status-grid">
                                    <div class="status-card">
                                        <div class="status-number" id="totalCount">0</div>
                                        <div class="status-label">Total</div>
                                    </div>
                                    <div class="status-card">
                                        <div class="status-number" id="sentCount">0</div>
                                        <div class="status-label">Sent</div>
                                    </div>
                                    <div class="status-card">
                                        <div class="status-number" id="pendingCount">0</div>
                                        <div class="status-label">Pending</div>
                                    </div>
                                    <div class="status-card">
                                        <div class="status-number" id="failedCount">0</div>
                                        <div class="status-label">Failed</div>
                                    </div>
                                </div>

                                <!-- Progress Ring -->
                                <div class="progress-ring">
                                    <svg>
                                        <defs>
                                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" style="stop-color:#00d4ff;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#0099cc;stop-opacity:1" />
                                            </linearGradient>
                                        </defs>
                                        <circle class="progress-ring-circle progress-ring-bg" cx="60" cy="60" r="52"></circle>
                                        <circle class="progress-ring-circle progress-ring-progress" cx="60" cy="60" r="52" 
                                                stroke-dasharray="326.73" stroke-dashoffset="326.73" id="progressRing"></circle>
                                    </svg>
                                    <div class="progress-text" id="progressPercent">0%</div>
                                </div>

                                <div class="mb-3 text-center">
                                    <small class="text-muted">Campaign Status: <span id="campaignStatus" class="badge bg-secondary">Unknown</span></small>
                                </div>

                                <div class="recipient-table">
                                    <h6 class="text-muted mb-2">Recent Activity</h6>
                                    <div id="recentRecipients">
                                        <small class="text-muted">No activity yet</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <div id="alertContainer"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const body = document.body;
            const toggle = document.querySelector('.toggle-switch');
            const sunIcon = document.querySelector('.fa-sun');
            const moonIcon = document.querySelector('.fa-moon');
            
            // Check current theme
            const currentTheme = body.getAttribute('data-theme');
            
            if (currentTheme === 'light') {
                // Switch to dark theme
                body.setAttribute('data-theme', 'dark');
                toggle.classList.add('active');
                sunIcon.style.opacity = '0.5';
                moonIcon.style.opacity = '1';
                localStorage.setItem('theme', 'dark');
            } else {
                // Switch to light theme
                body.setAttribute('data-theme', 'light');
                toggle.classList.remove('active');
                sunIcon.style.opacity = '1';
                moonIcon.style.opacity = '0.5';
                localStorage.setItem('theme', 'light');
            }
            
            // Force text visibility after theme change
            setTimeout(forceTextVisibility, 100);
        }

        // Initialize theme on page load
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const body = document.body;
            const toggle = document.querySelector('.toggle-switch');
            const sunIcon = document.querySelector('.fa-sun');
            const moonIcon = document.querySelector('.fa-moon');
            
            body.setAttribute('data-theme', savedTheme);
            
            if (savedTheme === 'light') {
                toggle.classList.remove('active');
                sunIcon.style.opacity = '1';
                moonIcon.style.opacity = '0.5';
            } else {
                toggle.classList.add('active');
                sunIcon.style.opacity = '0.5';
                moonIcon.style.opacity = '1';
            }
        }

        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tabs
            document.getElementById('dashboard-tab').style.display = 'none';
            document.getElementById('campaign-tab').style.display = 'none';
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected tab
            if (tabName === 'dashboard') {
                document.getElementById('dashboard-tab').style.display = 'block';
                document.querySelector('.nav-link[onclick="showTab(\'dashboard\')"]').classList.add('active');
            } else if (tabName === 'campaign') {
                document.getElementById('campaign-tab').style.display = 'block';
                document.querySelector('.nav-link[onclick="showTab(\'campaign\')"]').classList.add('active');
            }
        }


        // Update progress ring
        function updateProgressRing(percentage) {
            const circle = document.getElementById('progressRing');
            const radius = 52;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (percentage / 100) * circumference;
            
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = offset;
        }

        class CampaignManager {
            constructor() {
                this.currentCampaignId = null;
                this.pollingInterval = null;
                this.csvData = null;
                this.init();
            }

            init() {
                this.loadSenders();
                this.loadSmtpConfigurations();
                this.setupEventListeners();
                this.loadDashboardData();
            }

            getAuthToken() {
                // Get the CSRF token from meta tag
                return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            }

            getAuthHeaders() {
                return {
                    'X-CSRF-TOKEN': this.getAuthToken(),
                    'Accept': 'application/json'
                };
            }

            async loadSmtpConfigurations() {
                try {
                    console.log('Loading SMTP configurations...');
                    
                    const response = await fetch('/api/smtp-configurations/active', {
                        headers: this.getAuthHeaders()
                    });
                    
                    console.log('SMTP Response status:', response.status);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('SMTP Response data:', data);
                    
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load SMTP configurations');
                    }
                    
                    const select = document.getElementById('smtpConfigSelect');
                    select.innerHTML = '<option value="">Select SMTP Configuration...</option>';
                    
                    if (data.configurations && data.configurations.length > 0) {
                        data.configurations.forEach(config => {
                            const option = document.createElement('option');
                            option.value = config.id;
                            option.textContent = `${config.name} (${config.from_address})`;
                            if (config.is_default) {
                                option.textContent += ' - Default';
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                        console.log('Loaded', data.configurations.length, 'SMTP configurations');
                    } else {
                        select.innerHTML = '<option value="">No SMTP configurations available</option>';
                        console.log('No SMTP configurations found');
                    }
                } catch (error) {
                    console.error('Error loading SMTP configurations:', error);
                    this.showAlert('Failed to load SMTP configurations: ' + error.message, 'danger');
                    
                    const select = document.getElementById('smtpConfigSelect');
                    select.innerHTML = '<option value="">Error loading SMTP configurations</option>';
                }
            }

            async loadSenders() {
                try {
                    console.log('Loading senders...');
                    console.log('CSRF Token:', this.getAuthToken());
                    console.log('Headers:', this.getAuthHeaders());
                    
                    const response = await fetch('/api/senders', {
                        headers: this.getAuthHeaders()
                    });
                    
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load senders');
                    }
                    
                    const select = document.getElementById('senderSelect');
                    select.innerHTML = '<option value="">Select a sender...</option>';
                    
                    if (data.senders && data.senders.length > 0) {
                        data.senders.forEach(sender => {
                            const option = document.createElement('option');
                            option.value = sender.id;
                            option.textContent = `${sender.name} (${sender.from_address})`;
                            option.dataset.email = sender.from_address; // Store email for mapping
                            select.appendChild(option);
                        });
                        console.log('Loaded', data.senders.length, 'senders');
                    } else {
                        select.innerHTML = '<option value="">No senders available</option>';
                        console.log('No senders found');
                    }
                } catch (error) {
                    console.error('Error loading senders:', error);
                    this.showAlert('Failed to load senders: ' + error.message, 'danger');
                    
                    const select = document.getElementById('senderSelect');
                    select.innerHTML = '<option value="">Error loading senders</option>';
                }
            }

            // Auto-select SMTP configuration based on sender
            autoSelectSmtpConfig(senderEmail) {
                const smtpSelect = document.getElementById('smtpConfigSelect');
                const options = smtpSelect.querySelectorAll('option');
                
                // Find matching SMTP configuration based on email
                for (let option of options) {
                    if (option.value && option.textContent.includes(senderEmail)) {
                        smtpSelect.value = option.value;
                        console.log('Auto-selected SMTP config:', option.textContent);
                        break;
                    }
                }
            }

            async loadDashboardData() {
                try {
                    const response = await fetch('/api/dashboard', {
                        headers: this.getAuthHeaders()
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        this.updateDashboardDisplay(data.data);
                    } else {
                        console.error('Failed to load dashboard data:', data.message);
                    }
                } catch (error) {
                    console.error('Error loading dashboard data:', error);
                }
            }

            updateDashboardDisplay(dashboardData) {
                // Update status cards
                document.getElementById('totalCount').textContent = dashboardData.total_campaigns;
                document.getElementById('sentCount').textContent = dashboardData.total_recipients;
                document.getElementById('pendingCount').textContent = dashboardData.status_counts.queued + dashboardData.status_counts.sending;
                document.getElementById('failedCount').textContent = dashboardData.status_counts.failed;
                
                // Update progress ring
                updateProgressRing(dashboardData.overall_progress);
                document.getElementById('progressPercent').textContent = dashboardData.overall_progress + '%';
                
                // Update campaign status
                const statusBadge = document.getElementById('campaignStatus');
                if (dashboardData.total_campaigns > 0) {
                    const completedCount = dashboardData.status_counts.completed;
                    const totalCount = dashboardData.total_campaigns;
                    statusBadge.textContent = `${completedCount}/${totalCount} Completed`;
                    statusBadge.className = 'badge bg-success';
                } else {
                    statusBadge.textContent = 'No Campaigns';
                    statusBadge.className = 'badge bg-secondary';
                }

                // Update recent activity
                this.updateRecentActivity(dashboardData.recent_campaigns);
            }

            updateRecentActivity(recentCampaigns) {
                const container = document.getElementById('recentRecipients');
                
                if (recentCampaigns.length === 0) {
                    container.innerHTML = '<small class="text-muted">No campaigns yet</small>';
                    return;
                }

                container.innerHTML = recentCampaigns.map(campaign => {
                    const statusIcon = this.getCampaignStatusIcon(campaign.status);
                    const statusColor = this.getCampaignStatusColor(campaign.status);
                    
                    return `
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2" style="background: rgba(255,255,255,0.02); border-radius: 8px;">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-truncate" style="max-width: 200px;" title="${campaign.name}">${campaign.name}</div>
                                <small class="text-muted">${campaign.subject}</small>
                                <div class="small text-muted">${campaign.created_at} • ${campaign.total_recipients} recipients</div>
                            </div>
                            <span class="badge bg-${statusColor} ms-2">${statusIcon}</span>
                        </div>
                    `;
                }).join('');
            }

            getCampaignStatusIcon(status) {
                const icons = {
                    'completed': '✅',
                    'sending': '📤',
                    'queued': '⏳',
                    'draft': '📝',
                    'failed': '❌'
                };
                return icons[status] || '❓';
            }

            getCampaignStatusColor(status) {
                const colors = {
                    'completed': 'success',
                    'sending': 'warning',
                    'queued': 'info',
                    'draft': 'secondary',
                    'failed': 'danger'
                };
                return colors[status] || 'secondary';
            }

            setupEventListeners() {
                // Campaign form submission
                document.getElementById('campaignForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.createCampaign();
                });

                // Sender selection change event
                const senderSelect = document.getElementById('senderSelect');
                if (senderSelect) {
                    senderSelect.addEventListener('change', (e) => {
                        const selectedOption = e.target.options[e.target.selectedIndex];
                        if (selectedOption.dataset.email) {
                            this.autoSelectSmtpConfig(selectedOption.dataset.email);
                        }
                    });
                }

                // Live dashboard event listeners
                const refreshLiveStatsBtn = document.getElementById('refreshLiveStats');
                if (refreshLiveStatsBtn) {
                    refreshLiveStatsBtn.addEventListener('click', () => {
                        this.refreshLiveStats();
                    });
                }
            }

            // CSV file handling
            handleCsvFile(file) {
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    const csv = e.target.result;
                    this.parseCsv(csv);
                };
                reader.readAsText(file);
            }

            // Sample CSV download
            downloadSampleCsv() {
                const csvContent = "email,name\njohn@example.com,John Doe\njane@example.com,Jane Smith";
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'sample_recipients.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            }

            validateEmails(emailText) {
                const emails = emailText.split('\n').map(email => email.trim()).filter(email => email);
                const validEmails = emails.filter(email => {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test(email);
                });

                const invalidCount = emails.length - validEmails.length;
                if (invalidCount > 0) {
                    this.showAlert(`${invalidCount} invalid email(s) detected`, 'warning');
                }

                if (emails.length > 500) {
                    this.showAlert('Maximum 500 recipients allowed', 'danger');
                }
            }

            handleCsvFile(file) {
                if (!file) return;
                
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (fileExtension === 'csv') {
                    this.handleCsvFileContent(file);
                } else if (fileExtension === 'xls' || fileExtension === 'xlsx') {
                    this.showAlert('Excel files are not supported directly. Please convert your Excel file to CSV format and upload again. You can do this by opening the file in Excel and saving as CSV.', 'warning');
                } else {
                    this.showAlert('Please select a CSV file', 'danger');
                }
            }
            
            handleCsvFileContent(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const csvData = this.parseCsv(e.target.result);
                        this.displayCsvPreview(csvData);
                    } catch (error) {
                        this.showAlert('Error parsing CSV file: ' + error.message, 'danger');
                    }
                };
                reader.readAsText(file);
            }
            

            parseCsv(csvText) {
                const lines = csvText.split('\n').filter(line => line.trim());
                const data = [];

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (!line) continue;

                    // Simple CSV parsing (handles basic cases)
                    const columns = this.parseCsvLine(line);
                    if (columns.length > 0) {
                        const email = columns[0].trim();
                        const name = columns[1] ? columns[1].trim() : '';
                        
                        if (email) {
                            data.push({ email, name });
                        }
                    }
                }

                return data;
            }

            parseCsvLine(line) {
                const result = [];
                let current = '';
                let inQuotes = false;

                for (let i = 0; i < line.length; i++) {
                    const char = line[i];
                    
                    if (char === '"') {
                        inQuotes = !inQuotes;
                    } else if (char === ',' && !inQuotes) {
                        result.push(current);
                        current = '';
                    } else {
                        current += char;
                    }
                }
                
                result.push(current);
                return result;
            }

            displayCsvPreview(data) {
                const previewDiv = document.getElementById('csvPreview');
                const previewBody = document.getElementById('csvPreviewBody');
                const countSpan = document.getElementById('csvCount');

                // Validate emails
                const validData = data.filter(item => {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test(item.email);
                });

                if (validData.length === 0) {
                    this.showAlert('No valid email addresses found in CSV', 'danger');
                    previewDiv.style.display = 'none';
                    return;
                }

                if (validData.length > 500) {
                    this.showAlert('CSV contains more than 500 recipients. Only the first 500 will be used.', 'warning');
                    validData.splice(500);
                }

                // Display preview (show first 10 rows)
                previewBody.innerHTML = '';
                const previewRows = validData.slice(0, 10);
                
                previewRows.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.email}</td>
                        <td>${item.name || '-'}</td>
                    `;
                    previewBody.appendChild(row);
                });

                if (validData.length > 10) {
                    const moreRow = document.createElement('tr');
                    moreRow.innerHTML = `
                        <td colspan="2" class="text-center text-muted">
                            ... and ${validData.length - 10} more rows
                        </td>
                    `;
                    previewBody.appendChild(moreRow);
                }

                countSpan.textContent = validData.length;
                previewDiv.style.display = 'block';

                // Store the parsed data for form submission
                this.csvData = validData;
            }

            downloadSampleCsv() {
                // Download sample CSV from server
                const a = document.createElement('a');
                a.href = '/sample-csv';
                a.download = 'sample_recipients.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }

            async createCampaign() {
                const form = document.getElementById('campaignForm');
                const submitBtn = document.getElementById('submitBtn');
                
                // Disable form
                form.classList.add('loading');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Campaign...';

                try {
                    const formData = new FormData(form);
                    
                    // Check if CSV data is available
                    if (this.csvData && this.csvData.length > 0) {
                        // Convert CSV data to newline-separated string
                        const csvEmails = this.csvData.map(item => item.email).join('\n');
                        formData.set('recipients', csvEmails);
                    } else {
                        // Use manual entry if no CSV data
                        const manualRecipients = document.getElementById('recipients').value;
                        if (manualRecipients.trim()) {
                            formData.set('recipients', manualRecipients);
                        } else {
                            this.showAlert('Please provide recipients either manually or via CSV upload', 'warning');
                            // Re-enable form
                            form.classList.remove('loading');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Create Campaign & Start Sending';
                            return;
                        }
                    }

                    const response = await fetch('/api/campaigns', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getAuthToken()
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.currentCampaignId = data.campaign_id;
                        this.showAlert(`Campaign created successfully! ${data.total_recipients} emails queued.`, 'success');
                        this.showStatusCard();
                        this.showLiveDashboard(data.campaign);
                        this.startPolling();
                        form.reset();
                        
                        // Clear CSV data and preview
                        this.csvData = null;
                        document.getElementById('csvPreview').style.display = 'none';
                        document.getElementById('csvFile').value = '';
                    } else {
                        this.showAlert(data.message || 'Failed to create campaign', 'danger');
                    }
                } catch (error) {
                    this.showAlert('Network error: ' + error.message, 'danger');
                } finally {
                    // Re-enable form
                    form.classList.remove('loading');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Create Campaign & Start Sending';
                }
            }

            showStatusCard() {
                document.getElementById('statusCard').style.display = 'block';
            }

            // Live Dashboard Methods
            showLiveDashboard(campaign) {
                const dashboard = document.getElementById('liveCampaignDashboard');
                dashboard.style.display = 'block';
                
                // Update campaign info
                document.getElementById('liveCampaignName').textContent = campaign.name || 'Unknown Campaign';
                document.getElementById('liveCampaignStatus').textContent = campaign.status || 'Unknown';
                document.getElementById('liveCampaignStatus').className = `badge bg-${this.getStatusColor(campaign.status)}`;
                
                // Initialize stats
                this.updateLiveStats({
                    total_recipients: campaign.total_recipients || 0,
                    sent: 0,
                    pending: campaign.total_recipients || 0,
                    failed: 0
                });
                
                // Add initial activity
                this.addLiveActivity('Campaign created and queued for sending', 'info');
            }

            updateLiveStats(stats) {
                document.getElementById('liveTotalRecipients').textContent = stats.total_recipients || 0;
                document.getElementById('liveSentCount').textContent = stats.sent || 0;
                document.getElementById('livePendingCount').textContent = stats.pending || 0;
                document.getElementById('liveFailedCount').textContent = stats.failed || 0;
                
                // Calculate progress
                const total = stats.total_recipients || 0;
                const completed = stats.sent || 0;
                const progress = total > 0 ? Math.round((completed / total) * 100) : 0;
                
                document.getElementById('liveProgressPercent').textContent = `${progress}%`;
                document.getElementById('liveProgressBar').style.width = `${progress}%`;
                
                // Update progress bar color based on progress
                const progressBar = document.getElementById('liveProgressBar');
                if (progress === 100) {
                    progressBar.className = 'progress-bar bg-success';
                } else if (progress > 50) {
                    progressBar.className = 'progress-bar bg-warning';
                } else {
                    progressBar.className = 'progress-bar bg-primary';
                }
            }

            addLiveActivity(message, type = 'info') {
                const activityFeed = document.getElementById('liveActivityFeed');
                const now = new Date();
                const timeString = now.toLocaleTimeString();
                
                const activityItem = document.createElement('div');
                activityItem.className = 'activity-item';
                
                const iconClass = {
                    'info': 'fas fa-info-circle text-primary',
                    'success': 'fas fa-check-circle text-success',
                    'warning': 'fas fa-exclamation-triangle text-warning',
                    'danger': 'fas fa-times-circle text-danger',
                    'sending': 'fas fa-paper-plane text-info'
                }[type] || 'fas fa-info-circle text-primary';
                
                activityItem.innerHTML = `
                    <div class="activity-icon bg-light">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="text-sm">${message}</div>
                        <div class="activity-time">${timeString}</div>
                    </div>
                `;
                
                // Add to top of feed
                if (activityFeed.children.length > 0) {
                    activityFeed.insertBefore(activityItem, activityFeed.firstChild);
                } else {
                    activityFeed.innerHTML = '';
                    activityFeed.appendChild(activityItem);
                }
                
                // Limit to 10 items
                while (activityFeed.children.length > 10) {
                    activityFeed.removeChild(activityFeed.lastChild);
                }
            }

            async refreshLiveStats() {
                if (!this.currentCampaignId) return;
                
                try {
                    const response = await fetch(`/api/campaigns/${this.currentCampaignId}/status`, {
                        headers: this.getAuthHeaders()
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        this.updateLiveStats(data.stats);
                        this.addLiveActivity(`Stats refreshed - ${data.stats.sent} sent, ${data.stats.pending} pending`, 'info');
                    }
                } catch (error) {
                    console.error('Error refreshing live stats:', error);
                }
            }

            startPolling() {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                }

                this.pollingInterval = setInterval(() => {
                    this.updateStatus();
                }, 2000); // Poll every 2 seconds

                // Initial status update
                this.updateStatus();
            }

            async updateStatus() {
                if (!this.currentCampaignId) return;

                try {
                    const response = await fetch(`/api/campaigns/${this.currentCampaignId}/status`, {
                        headers: this.getAuthHeaders()
                    });
                    const data = await response.json();

                    if (data.success) {
                        this.updateStatusDisplay(data);
                        this.updateLiveStats(data.stats);
                        
                        // Add activity based on status changes
                        if (data.stats.sent > 0) {
                            this.addLiveActivity(`${data.stats.sent} emails sent successfully`, 'success');
                        }
                        if (data.stats.failed > 0) {
                            this.addLiveActivity(`${data.stats.failed} emails failed`, 'danger');
                        }
                        
                        if (data.campaign_status === 'completed') {
                            this.addLiveActivity('Campaign completed successfully!', 'success');
                            clearInterval(this.pollingInterval);
                        }
                    }
                } catch (error) {
                    console.error('Failed to update status:', error);
                }
            }

            updateStatusDisplay(data) {
                document.getElementById('totalCount').textContent = data.total;
                document.getElementById('sentCount').textContent = data.sent;
                document.getElementById('pendingCount').textContent = data.pending;
                document.getElementById('failedCount').textContent = data.failed;
                
                const progressPercent = data.total > 0 ? Math.round((data.sent + data.failed) / data.total * 100) : 0;
                document.getElementById('progressPercent').textContent = progressPercent + '%';
                
                // Update progress ring
                updateProgressRing(progressPercent);
                
                // Update campaign status badge
                const statusBadge = document.getElementById('campaignStatus');
                statusBadge.textContent = data.campaign_status;
                statusBadge.className = 'badge bg-' + this.getStatusColor(data.campaign_status);

                // Update recent recipients
                this.updateRecentRecipients(data.recent_recipients);
            }

            getStatusColor(status) {
                const colors = {
                    'draft': 'secondary',
                    'queued': 'info',
                    'sending': 'warning',
                    'completed': 'success'
                };
                return colors[status] || 'secondary';
            }

            updateRecentRecipients(recipients) {
                const container = document.getElementById('recentRecipients');
                
                if (recipients.length === 0) {
                    container.innerHTML = '<small class="text-muted">No activity yet</small>';
                    return;
                }

                container.innerHTML = recipients.map(recipient => {
                    const statusIcon = this.getStatusIcon(recipient.status);
                    const statusColor = this.getStatusColor(recipient.status);
                    const sentTime = recipient.sent_at ? new Date(recipient.sent_at).toLocaleTimeString() : '';
                    
                    return `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-truncate me-2" style="max-width: 150px;">${recipient.email}</small>
                            <span class="badge bg-${statusColor}">${statusIcon}</span>
                        </div>
                        ${recipient.last_error ? `<small class="text-danger">${recipient.last_error}</small>` : ''}
                        ${sentTime ? `<small class="text-muted">${sentTime}</small>` : ''}
                    `;
                }).join('');
            }

            getStatusIcon(status) {
                const icons = {
                    'pending': '⏳',
                    'sent': '✅',
                    'failed': '❌'
                };
                return icons[status] || '❓';
            }

            stopPolling() {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
            }

            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertId = 'alert-' + Date.now();
                
                const alertHtml = `
                    <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                alertContainer.insertAdjacentHTML('beforeend', alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    const alert = document.getElementById(alertId);
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
        }

        // Force text color visibility
        function forceTextVisibility() {
            // Fix input elements
            const inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.style.color = 'var(--text-primary)';
                input.style.backgroundColor = 'var(--secondary-bg)';
                
                // Add event listeners to maintain color
                input.addEventListener('focus', function() {
                    this.style.color = 'var(--text-primary)';
                    this.style.backgroundColor = 'var(--card-bg)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.color = 'var(--text-primary)';
                    this.style.backgroundColor = 'var(--secondary-bg)';
                });
                
                input.addEventListener('input', function() {
                    this.style.color = 'var(--text-primary)';
                });
            });

            // Fix form helper text and labels
            const formTexts = document.querySelectorAll('.form-text, .form-label, small, .small, .text-muted');
            formTexts.forEach(element => {
                if (element.classList.contains('form-label')) {
                    element.style.color = 'var(--text-primary)';
                } else {
                    element.style.color = 'var(--text-secondary)';
                }
            });

            // Fix tab content text
            const tabContents = document.querySelectorAll('.tab-content h6, .tab-content small');
            tabContents.forEach(element => {
                if (element.tagName === 'H6') {
                    element.style.color = 'var(--text-primary)';
                } else {
                    element.style.color = 'var(--text-secondary)';
                }
            });

            // Fix alert text
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-info')) {
                    alert.style.color = 'var(--accent-color)';
                }
            });

            // Fix table text
            const tableElements = document.querySelectorAll('.table th, .table td');
            tableElements.forEach(element => {
                element.style.color = 'var(--text-primary)';
            });
        }

        // Initialize the campaign manager when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize theme first
            initializeTheme();
            
            window.campaignManager = new CampaignManager();
            
            // Show dashboard tab by default
            showTab('dashboard');
            
            // Force text visibility
            forceTextVisibility();
            
            // Re-apply text visibility and refresh dashboard when switching tabs
            const originalShowTab = showTab;
            showTab = function(tabName) {
                originalShowTab(tabName);
                setTimeout(forceTextVisibility, 100); // Small delay to ensure DOM is updated
                
                // Refresh dashboard data when switching to dashboard tab
                if (tabName === 'dashboard') {
                    const campaignManager = window.campaignManager;
                    if (campaignManager) {
                        campaignManager.loadDashboardData();
                    }
                }
            };
            
            // Add smooth animations to status cards
            const statusCards = document.querySelectorAll('.status-card');
            statusCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-in');
            });
        });
    </script>
</body>
</html>
