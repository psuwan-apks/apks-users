<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Date Default TimeZone set
date_default_timezone_set('Asia/Bangkok');

// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(dirname(__FILE__, 2));
$dir_app = $dir_root . DS . 'app';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $dir_app);

// Load settings and configurations
require_once APPLICATION_PATH . DS . 'config' . DS . 'config.php';

$menuJsonPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'menu' . DIRECTORY_SEPARATOR . 'sidebar.json';

// Handle POST request to save menu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $menuDataJson = $_POST['menu_data'] ?? '';
        
        if (empty($menuDataJson)) {
            echo json_encode(['success' => false, 'message' => 'No menu data received.']);
            exit;
        }

        $menuData = json_decode($menuDataJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON structure: ' . json_last_error_msg()]);
            exit;
        }

        if (!is_array($menuData)) {
            echo json_encode(['success' => false, 'message' => 'Menu data must be a valid array.']);
            exit;
        }

        // Create backup of current sidebar.json
        if (file_exists($menuJsonPath)) {
            $backupPath = $menuJsonPath . '.bak';
            @copy($menuJsonPath, $backupPath);
        }

        // Write the updated JSON back to file
        $jsonOutput = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $bytes = @file_put_contents($menuJsonPath, $jsonOutput, LOCK_EX);

        if ($bytes !== false) {
            echo json_encode(['success' => true, 'message' => 'Menu layout saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to write to sidebar.json. Check file permissions.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request action.']);
    exit;
}

// Load current menu for GET request
$currentMenu = [];
if (file_exists($menuJsonPath)) {
    $json = @file_get_contents($menuJsonPath);
    if ($json) {
        $currentMenu = json_decode($json, true) ?: [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Developer Sidebar Menu Manager</title>
    
    <!-- Local and CDN Assets -->
    <link rel="stylesheet" href="../../assets/fonts/google-sans/google-sans.css">
    <link rel="stylesheet" href="../../assets/fonts/fontawesome/css/all.css">
    <link href="../../assets/bootstrap-5.3.8/css/bootstrap.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/sweetalert2/sweetalert2.min.css" />
    <link rel="stylesheet" href="../../assets/select2/css/select2.min.css" />
    
    <style>
        * {
            font-family: 'Google Sans', sans-serif;
        }
        /* Select2 Custom Styles to match Bootstrap 5 and Google Sans */
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: 0;
            padding-right: 0;
            color: #212529;
            line-height: inherit;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 8px;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #888 transparent transparent transparent;
        }
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #1abc9c !important;
            color: #fff !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: .25rem;
            padding: 4px 8px;
            outline: none;
        }
        .select2-dropdown {
            border: 1px solid rgba(0,0,0,.15);
            border-radius: .375rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.175);
            z-index: 9999;
        }
        body {
            background-color: #f4f7f9;
            color: #2d3436;
        }
        .header-banner {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: #ffffff;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .menu-sortable-container {
            min-height: 100px;
        }
        .menu-item-card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 6px;
            margin-bottom: 8px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .menu-item-card:hover {
            border-color: #1abc9c;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .menu-handle {
            cursor: grab;
            padding: 15px;
            color: #b2bec3;
            transition: color 0.2s;
        }
        .menu-handle:hover {
            color: #636e72;
        }
        .menu-item-details {
            padding: 12px 0;
        }
        .sub-menu-list {
            margin-left: 45px;
            min-height: 40px;
            border-left: 2px dashed #dfe6e9;
            padding-left: 15px;
            padding-top: 5px;
            padding-bottom: 10px;
        }
        .sub-menu-list:empty::after {
            content: "Empty Folder - Drag links here";
            display: block;
            padding: 10px;
            color: #b2bec3;
            font-size: 0.8rem;
            font-style: italic;
        }
        .sortable-ghost {
            opacity: 0.4;
            background: #e8f8f5;
            border: 1px dashed #1abc9c;
        }
        .sortable-chosen {
            background: #fafafa;
        }
        .font-weight-bold {
            font-weight: 600;
        }
        .credits-text {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 50px;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Banner -->
    <div class="header-banner">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="h3 font-weight-bold mb-1"><i class="fa-thin fa-sliders me-2"></i>Sidebar Menu Manager</h1>
                    <p class="mb-0 opacity-75 small">Developer utility to manage, edit, and re-order sidebar menu definitions.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="./" class="btn btn-outline-light me-2"><i class="fa-thin fa-arrow-left me-1"></i> Back to App</a>
                    <button class="btn btn-light px-4 font-weight-bold" id="save-menu-btn" onclick="saveMenuLayout()">
                        <i class="fa-thin fa-floppy-disk me-1 text-success"></i> Save Layout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="container">
        <div class="row">
            <!-- Left Panel: Menu Structure -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-weight-bold">Menu Hierarchy</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="addMenuItem('item')">
                                <i class="fa-thin fa-plus me-1"></i> Add Single Item
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="addMenuItem('collapse')">
                                <i class="fa-thin fa-folder-plus me-1"></i> Add Collapsible Menu
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div id="menu-list" class="list-group menu-sortable-container">
                            <!-- Dynamic items rendered by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Guide & Tool Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="font-weight-bold mb-3"><i class="fa-thin fa-circle-info text-primary me-1"></i> Interactive Guide</h5>
                        <ul class="list-unstyled mb-0" style="font-size: 0.9rem; line-height: 1.6;">
                            <li class="mb-2">
                                <i class="fa-thin fa-arrows-up-down text-muted me-2"></i>
                                <strong>Reordering:</strong> Drag items vertically to change their order.
                            </li>
                            <li class="mb-2">
                                <i class="fa-thin fa-arrows-left-right text-muted me-2"></i>
                                <strong>Nesting:</strong> Drag items inside collapsible folders to make them child items.
                            </li>
                            <li class="mb-2">
                                <i class="fa-thin fa-reply text-muted me-2"></i>
                                <strong>Un-nesting:</strong> Drag items out of folders and back into the main list.
                            </li>
                            <li class="mb-2">
                                <i class="fa-thin fa-anchor text-muted me-2"></i>
                                <strong>Position:</strong> Items marked as <em>Bottom</em> align to the bottom of the sidebar.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                    <div class="card-body p-4">
                        <h5 class="font-weight-bold mb-2"><i class="fa-thin fa-shield-halved me-1"></i> Autorestore Backup</h5>
                        <p class="small mb-0 opacity-75">Every save stores a copy of the prior configuration to <code>app/menu/sidebar.json.bak</code> in case you need to rollback manually.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="credits-text">
            APKS Software House &bull; Developer Menu Console
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="menuItemModal" tabindex="-1" aria-labelledby="menuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-weight-bold" id="menuItemModalLabel">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="menu-item-form" onsubmit="event.preventDefault(); saveModalChanges();">
                        <input type="hidden" id="edit-item-id">
                        <input type="hidden" id="edit-parent-id">
                        
                        <div class="row g-3">
                            <!-- Menu Type -->
                            <div class="col-md-6">
                                <label for="item-type" class="form-label font-weight-bold text-muted small">Menu Type</label>
                                <select id="item-type" class="form-select select2">
                                    <option value="item">Single Link / Page Item</option>
                                    <option value="collapse">Collapsible Folder / Category</option>
                                </select>
                            </div>
                            
                            <!-- Position Group -->
                            <div class="col-md-6">
                                <label for="item-position" class="form-label font-weight-bold text-muted small">Position Group</label>
                                <select id="item-position" class="form-select select2">
                                    <option value="top">Top Section</option>
                                    <option value="bottom">Bottom Section</option>
                                </select>
                            </div>

                            <!-- Default Title -->
                            <div class="col-md-6">
                                <label for="item-title" class="form-label font-weight-bold text-muted small">Default Title (English/Fallback)</label>
                                <input type="text" id="item-title" class="form-control" placeholder="e.g. Settings" required>
                            </div>

                            <!-- Translation Key -->
                            <div class="col-md-6">
                                <label for="item-transkey" class="form-label font-weight-bold text-muted small">Translation Key (Optional)</label>
                                <input type="text" id="item-transkey" class="form-control" placeholder="e.g. NAV_SETTINGS">
                            </div>

                            <!-- Icon -->
                            <div class="col-md-6">
                                <label for="item-icon" class="form-label font-weight-bold text-muted small">FontAwesome Icon Class</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light" id="icon-preview-container"><i class="fa-thin fa-link" id="icon-preview"></i></span>
                                    <input type="text" id="item-icon" class="form-control" placeholder="e.g. fa-thin fa-cog" oninput="updateIconPreview()">
                                </div>
                            </div>

                            <!-- Mini Icon -->
                            <div class="col-md-6" id="field-container-mini-icon">
                                <label for="item-mini-icon" class="form-label font-weight-bold text-muted small">Mini Icon Text (Sub-items only)</label>
                                <input type="text" id="item-mini-icon" class="form-control" placeholder="e.g. GS" maxlength="3">
                            </div>

                            <!-- URL -->
                            <div class="col-12" id="field-container-url">
                                <label for="item-url" class="form-label font-weight-bold text-muted small">Link URL</label>
                                <input type="text" id="item-url" class="form-control" placeholder="e.g. ./?page=settings&action=general">
                            </div>

                            <hr class="my-4">
                            
                            <!-- Active Rules -->
                            <h6 class="font-weight-bold text-dark mb-2"><i class="fa-thin fa-route me-1 text-primary"></i> Active Rule Configuration</h6>
                            <p class="text-muted small mb-3">Defines when this menu item should be highlighted as "active".</p>

                            <!-- Rule Type -->
                            <div class="col-md-6">
                                <label for="rule-type" class="form-label font-weight-bold text-muted small">Rule Type</label>
                                <select id="rule-type" class="form-select select2">
                                    <option value="none">None (Never auto-highlight)</option>
                                    <option value="exact_page">Exact Page Matches</option>
                                    <option value="in_pages">In List of Pages</option>
                                    <option value="exact_page_action">Exact Page + Action Matches</option>
                                    <option value="uri_match">URI URL Match</option>
                                </select>
                            </div>

                            <!-- Rule Page -->
                            <div class="col-md-6" id="rule-container-page">
                                <label for="rule-page" class="form-label font-weight-bold text-muted small">Page ID</label>
                                <input type="text" id="rule-page" class="form-control" placeholder="e.g. guest">
                            </div>

                            <!-- Rule Pages -->
                            <div class="col-md-6" id="rule-container-pages">
                                <label for="rule-pages" class="form-label font-weight-bold text-muted small">Pages List (comma separated)</label>
                                <input type="text" id="rule-pages" class="form-control" placeholder="e.g. admin, customers, users">
                            </div>

                            <!-- Rule Action -->
                            <div class="col-md-6" id="rule-container-action">
                                <label for="rule-action" class="form-label font-weight-bold text-muted small">Action ID</label>
                                <input type="text" id="rule-action" class="form-control" placeholder="e.g. roles-view">
                            </div>

                            <!-- Rule URI -->
                            <div class="col-md-12" id="rule-container-uri">
                                <label for="rule-uri" class="form-label font-weight-bold text-muted small">URI Substring</label>
                                <input type="text" id="rule-uri" class="form-control" placeholder="e.g. examples/calendar.php">
                            </div>
                        </div>
                        
                        <div class="modal-footer border-0 px-0 pb-0 mt-4">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Apply Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notices -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 2000;">
        <div id="feedbackToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toast-message">
                    Changes applied.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap and jQuery CDNs / Local Fallbacks -->
    <script src="../../assets/bootstrap-5.3.8/js/jquery-4.0.0.min.js"></script>
    <script src="../../assets/bootstrap-5.3.8/js/popper.min.js"></script>
    <script src="../../assets/bootstrap-5.3.8/js/bootstrap.min.js"></script>
    <script src="../../assets/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../assets/select2/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <script>
        let menuData = <?php echo json_encode($currentMenu); ?>;
        const menuListEl = document.getElementById('menu-list');
        const itemModal = new bootstrap.Modal(document.getElementById('menuItemModal'));
        const toastEl = new bootstrap.Toast(document.getElementById('feedbackToast'));

        function generateId() {
            return 'menu_' + Math.random().toString(36).substr(2, 9);
        }

        function prepareData(items) {
            return items.map(item => {
                const newItem = { ...item };
                newItem._id = generateId();
                if (newItem.type === 'collapse' && newItem.children) {
                    newItem.children = prepareData(newItem.children);
                }
                return newItem;
            });
        }

        menuData = prepareData(menuData);

        function findItemById(id, items = menuData) {
            for (let item of items) {
                if (item._id === id) return { item, parent: null };
                if (item.type === 'collapse' && item.children) {
                    const found = findItemById(id, item.children);
                    if (found.item) {
                        return { item: found.item, parent: item };
                    }
                }
            }
            return { item: null, parent: null };
        }

        function deleteItem(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to delete this menu item?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const removeNode = (list) => {
                        const idx = list.findIndex(i => i._id === id);
                        if (idx !== -1) {
                            list.splice(idx, 1);
                            return true;
                        }
                        for (let item of list) {
                            if (item.type === 'collapse' && item.children) {
                                if (removeNode(item.children)) return true;
                            }
                        }
                        return false;
                    };
                    
                    removeNode(menuData);
                    showToast('Item deleted successfully.');
                    renderMenuEditor();
                }
            });
        }

        function editItem(id) {
            const { item, parent } = findItemById(id);
            if (!item) return;

            document.getElementById('menuItemModalLabel').textContent = 'Edit Menu Item';
            document.getElementById('edit-item-id').value = id;
            document.getElementById('edit-parent-id').value = parent ? parent._id : '';

            $('#item-type').val(item.type || 'item').trigger('change');
            $('#item-position').val(item.position === 'bottom' ? 'bottom' : 'top').trigger('change');
            document.getElementById('item-title').value = item.defaultTitle || '';
            document.getElementById('item-transkey').value = item.titleTransKey || '';
            document.getElementById('item-icon').value = item.icon || '';
            document.getElementById('item-mini-icon').value = item.miniIcon || '';
            document.getElementById('item-url').value = item.url || '';

            const rule = item.activeRule || {};
            $('#rule-type').val(rule.type || 'none').trigger('change');
            document.getElementById('rule-page').value = rule.page || '';
            document.getElementById('rule-pages').value = rule.pages ? rule.pages.join(', ') : '';
            document.getElementById('rule-action').value = rule.action || '';
            document.getElementById('rule-uri').value = rule.value || '';

            updateIconPreview();
            
            itemModal.show();
        }

        function addMenuItem(type) {
            document.getElementById('menuItemModalLabel').textContent = type === 'collapse' ? 'Add Collapsible Folder' : 'Add Single Menu Item';
            document.getElementById('menu-item-form').reset();
            document.getElementById('edit-item-id').value = '';
            document.getElementById('edit-parent-id').value = '';
            
            $('#item-type').val(type).trigger('change');
            $('#item-position').val('top').trigger('change');
            document.getElementById('item-icon').value = type === 'collapse' ? 'fa-thin fa-folder' : 'fa-thin fa-link';
            $('#rule-type').val('none').trigger('change');

            updateIconPreview();
            
            itemModal.show();
        }

        function toggleFormFields() {
            const type = document.getElementById('item-type').value;
            const urlContainer = document.getElementById('field-container-url');
            const miniIconContainer = document.getElementById('field-container-mini-icon');
            
            if (type === 'collapse') {
                urlContainer.style.display = 'none';
                miniIconContainer.style.display = 'none';
            } else {
                urlContainer.style.display = 'block';
                miniIconContainer.style.display = 'block';
            }
        }

        function toggleRuleFields() {
            const ruleType = document.getElementById('rule-type').value;
            
            document.getElementById('rule-container-page').style.display = 'none';
            document.getElementById('rule-container-pages').style.display = 'none';
            document.getElementById('rule-container-action').style.display = 'none';
            document.getElementById('rule-container-uri').style.display = 'none';

            if (ruleType === 'exact_page') {
                document.getElementById('rule-container-page').style.display = 'block';
            } else if (ruleType === 'in_pages') {
                document.getElementById('rule-container-pages').style.display = 'block';
            } else if (ruleType === 'exact_page_action') {
                document.getElementById('rule-container-page').style.display = 'block';
                document.getElementById('rule-container-action').style.display = 'block';
            } else if (ruleType === 'uri_match') {
                document.getElementById('rule-container-uri').style.display = 'block';
            }
        }

        function updateIconPreview() {
            const iconClass = document.getElementById('item-icon').value || 'fa-thin fa-link';
            document.getElementById('icon-preview').className = iconClass;
        }

        function saveModalChanges() {
            const id = document.getElementById('edit-item-id').value;
            const type = document.getElementById('item-type').value;
            const position = document.getElementById('item-position').value;
            const defaultTitle = document.getElementById('item-title').value;
            const titleTransKey = document.getElementById('item-transkey').value;
            const icon = document.getElementById('item-icon').value;
            const miniIcon = document.getElementById('item-mini-icon').value;
            const url = document.getElementById('item-url').value;

            const ruleType = document.getElementById('rule-type').value;
            let activeRule = null;
            if (ruleType !== 'none') {
                activeRule = { type: ruleType };
                if (ruleType === 'exact_page') {
                    activeRule.page = document.getElementById('rule-page').value;
                } else if (ruleType === 'in_pages') {
                    activeRule.pages = document.getElementById('rule-pages').value.split(',').map(s => s.trim()).filter(Boolean);
                } else if (ruleType === 'exact_page_action') {
                    activeRule.page = document.getElementById('rule-page').value;
                    activeRule.action = document.getElementById('rule-action').value;
                } else if (ruleType === 'uri_match') {
                    activeRule.value = document.getElementById('rule-uri').value;
                }
            }

            if (id) {
                const { item } = findItemById(id);
                if (item) {
                    item.type = type;
                    item.position = position;
                    item.defaultTitle = defaultTitle;
                    item.titleTransKey = titleTransKey;
                    item.icon = icon;
                    if (type === 'item') {
                        item.url = url;
                        item.miniIcon = miniIcon;
                    } else {
                        delete item.url;
                        delete item.miniIcon;
                        if (!item.children) item.children = [];
                    }
                    if (activeRule) {
                        item.activeRule = activeRule;
                    } else {
                        delete item.activeRule;
                    }
                }
            } else {
                const newItem = {
                    _id: generateId(),
                    type,
                    position,
                    defaultTitle,
                    titleTransKey,
                    icon
                };
                if (type === 'item') {
                    newItem.url = url;
                    newItem.miniIcon = miniIcon;
                } else {
                    newItem.children = [];
                }
                if (activeRule) {
                    newItem.activeRule = activeRule;
                }
                menuData.push(newItem);
            }

            itemModal.hide();
            showToast('Changes applied.');
            renderMenuEditor();
        }

        function showToast(message) {
            document.getElementById('toast-message').textContent = message;
            toastEl.show();
        }

        function renderItemNode(item) {
            const isCollapse = item.type === 'collapse';
            const activeRuleLabel = item.activeRule ? `<span class="badge bg-light text-dark border ms-2 small">Rule: ${item.activeRule.type}</span>` : '';
            const bottomLabel = item.position === 'bottom' ? '<span class="badge bg-warning text-dark border ms-2 small">Bottom Sec</span>' : '';
            
            let html = `
            <div class="menu-item-card list-group-item d-flex flex-column p-0 ${isCollapse ? 'is-collapse-item' : ''}" data-id="${item._id}">
                <div class="d-flex align-items-center">
                    <div class="menu-handle"><i class="fa-thin fa-grip-vertical"></i></div>
                    <div class="flex-shrink-0 me-3">
                        <i class="${item.icon || (isCollapse ? 'fa-thin fa-folder' : 'fa-thin fa-link')} fa-fw" style="font-size: 1.2rem;"></i>
                    </div>
                    <div class="menu-item-details flex-grow-1">
                        <div class="font-weight-bold d-flex align-items-center">
                            ${escapeHtml(item.defaultTitle)}
                            ${bottomLabel}
                            ${activeRuleLabel}
                        </div>
                        <div class="text-muted small">
                            ${isCollapse ? 'Folder' : escapeHtml(item.url || '#')} 
                            ${item.titleTransKey ? `&bull; Key: <code class="text-secondary">${escapeHtml(item.titleTransKey)}</code>` : ''}
                        </div>
                    </div>
                    <div class="pe-3">
                        <button class="btn btn-link btn-sm text-dark py-0" onclick="editItem('${item._id}')" title="Edit Item"><i class="fa-thin fa-edit"></i></button>
                        <button class="btn btn-link btn-sm text-danger py-0" onclick="deleteItem('${item._id}')" title="Delete Item"><i class="fa-thin fa-trash"></i></button>
                    </div>
                </div>`;

            if (isCollapse) {
                html += `
                <div class="sub-menu-list list-group" data-parent-id="${item._id}">`;
                if (item.children && item.children.length > 0) {
                    item.children.forEach(child => {
                        html += renderItemNode(child);
                    });
                }
                html += `
                </div>`;
            }

            html += `
            </div>`;
            return html;
        }

        function escapeHtml(string) {
            if (!string) return '';
            return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderMenuEditor() {
            menuListEl.innerHTML = '';
            
            if (menuData.length === 0) {
                menuListEl.innerHTML = '<div class="alert alert-info py-4 text-center">Menu is empty. Add a link or a collapsible folder above to begin.</div>';
                return;
            }

            menuData.forEach(item => {
                menuListEl.innerHTML += renderItemNode(item);
            });

            new Sortable(menuListEl, {
                group: {
                    name: 'nested',
                    pull: true,
                    put: true
                },
                animation: 150,
                handle: '.menu-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: onDragEnd
            });

            const subContainers = document.querySelectorAll('.sub-menu-list');
            subContainers.forEach(container => {
                new Sortable(container, {
                    group: {
                        name: 'nested',
                        pull: true,
                        put: function (to, from, dragEl) {
                            return !dragEl.classList.contains('is-collapse-item');
                        }
                    },
                    animation: 150,
                    handle: '.menu-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: onDragEnd
                });
            });
        }

        function onDragEnd() {
            const updatedMenu = [];

            function scanNode(element) {
                const id = element.getAttribute('data-id');
                const { item } = findItemById(id);
                if (!item) return null;

                const updatedItem = { ...item };
                
                if (updatedItem.type === 'collapse') {
                    const subList = element.querySelector('.sub-menu-list');
                    updatedItem.children = [];
                    if (subList) {
                        const childCards = subList.children;
                        for (let i = 0; i < childCards.length; i++) {
                            const childNode = scanNode(childCards[i]);
                            if (childNode) {
                                if (!childNode.miniIcon && childNode.defaultTitle) {
                                    childNode.miniIcon = childNode.defaultTitle.substring(0, 2).toUpperCase();
                                }
                                updatedItem.children.push(childNode);
                            }
                        }
                    }
                }
                return updatedItem;
            }

            const topCards = menuListEl.children;
            for (let i = 0; i < topCards.length; i++) {
                const topNode = scanNode(topCards[i]);
                if (topNode) {
                    updatedMenu.push(topNode);
                }
            }

            menuData = updatedMenu;
        }

        function saveMenuLayout() {
            const saveBtn = document.getElementById('save-menu-btn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';

            function cleanData(items) {
                return items.map(item => {
                    const newItem = { ...item };
                    delete newItem._id;
                    if (newItem.type === 'collapse' && newItem.children) {
                        newItem.children = cleanData(newItem.children);
                    }
                    return newItem;
                });
            }

            const payload = cleanData(menuData);
            
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('menu_data', JSON.stringify(payload));

            fetch('menu-manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Sidebar menu configuration saved successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: data.message,
                        confirmButtonColor: '#1abc9c'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred while saving the menu.',
                    confirmButtonColor: '#1abc9c'
                });
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa-thin fa-floppy-disk me-1 text-success"></i> Save Layout';
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderMenuEditor();

            // Make Swal global
            window.swal = Swal;

            // Initialize Select2 on modal shown
            const myModalEl = document.getElementById('menuItemModal');
            myModalEl.addEventListener('shown.bs.modal', function () {
                $('.select2').each(function() {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $('#menuItemModal')
                    });
                });
            });

            // Bind change events for select2 compatibility
            $('#item-type').on('change', function() {
                toggleFormFields();
            });
            $('#rule-type').on('change', function() {
                toggleRuleFields();
            });
        });
    </script>
</body>
</html>
