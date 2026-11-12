import axios from 'axios';

const ACTIVITY_LOG_ENDPOINT = '/api-digital/inventory/activity_logs.php';

export const INVENTORY_PAGE_NAMES = {
  overview: 'ภาพรวมระบบคลังพัสดุ',
  pr: 'ขอซื้อขอจ้าง (PR)',
  pr_form: 'สร้าง/แก้ไข ใบขอซื้อขอจ้าง (PR)',
  po: 'จัดซื้อจัดจ้าง (PO)',
  po_form: 'สร้าง/แก้ไข ใบสั่งซื้อสั่งจ้าง (PO)',
  receive: 'ตรวจรับพัสดุเข้าคลัง',
  requisitions: 'รายการเบิกวัสดุ',
  requisition_form: 'สร้างคำขอเบิกวัสดุ',
  issue: 'จ่ายวัสดุออกคลัง',
  transfer: 'โอนย้ายพัสดุข้ามคลัง',
  transfer_form: 'สร้างใบโอนย้ายพัสดุ',
  return: 'รับคืนพัสดุเข้าคลัง',
  stock_card: 'บัญชีคุมวัสดุ (Stock Card)',
  reports: 'รายงานและสรุปยอดคลังพัสดุ',
  settings: 'ตั้งค่าระบบ (Master Data)',
  logs: 'ประวัติการใช้งานระบบคลัง (Audit Log)',
};

const ROUTE_PAGE_MAP = {
  '/inventory-dashboard': { page_code: 'overview', page_name: INVENTORY_PAGE_NAMES.overview },
  '/inventory-pr': { page_code: 'pr', page_name: INVENTORY_PAGE_NAMES.pr },
  '/inventory-pr-form': { page_code: 'pr_form', page_name: INVENTORY_PAGE_NAMES.pr_form },
  '/inventory-po': { page_code: 'po', page_name: INVENTORY_PAGE_NAMES.po },
  '/inventory-po-form': { page_code: 'po_form', page_name: INVENTORY_PAGE_NAMES.po_form },
  '/inventory-receive': { page_code: 'receive', page_name: INVENTORY_PAGE_NAMES.receive },
  '/inventory-requisitions': { page_code: 'requisitions', page_name: INVENTORY_PAGE_NAMES.requisitions },
  '/inventory-requisition-form': { page_code: 'requisition_form', page_name: INVENTORY_PAGE_NAMES.requisition_form },
  '/inventory-issue': { page_code: 'issue', page_name: INVENTORY_PAGE_NAMES.issue },
  '/inventory-stock-card': { page_code: 'stock_card', page_name: INVENTORY_PAGE_NAMES.stock_card },
  '/inventory-reports': { page_code: 'reports', page_name: INVENTORY_PAGE_NAMES.reports },
  '/inventory-settings': { page_code: 'settings', page_name: INVENTORY_PAGE_NAMES.settings },
  '/inventory-transfer': { page_code: 'transfer', page_name: INVENTORY_PAGE_NAMES.transfer },
  '/inventory-transfer-form': { page_code: 'transfer_form', page_name: INVENTORY_PAGE_NAMES.transfer_form },
  '/inventory-return': { page_code: 'return', page_name: INVENTORY_PAGE_NAMES.return },
  '/inventory-logs': { page_code: 'logs', page_name: INVENTORY_PAGE_NAMES.logs },
};

const recentEventTimestamps = new Map();

function readCurrentWarehouseContext() {
  try {
    const whId = localStorage.getItem('selected_warehouse_id');
    const whName = localStorage.getItem('selected_warehouse_name');
    if (whId) {
      return {
        warehouse_id: Number(whId) || null,
        warehouse_name: whName || '',
      };
    }
    const raw = localStorage.getItem('selected_inventory_warehouse');
    if (raw) {
      const parsed = JSON.parse(raw);
      return {
        warehouse_id: parsed?.id ? Number(parsed.id) : null,
        warehouse_name: parsed?.name || '',
      };
    }
  } catch (e) {
    // ignore invalid localStorage json
  }
  return { warehouse_id: null, warehouse_name: '' };
}

function readCurrentUserContext() {
  try {
    const raw = localStorage.getItem('user_info');
    if (raw) {
      const parsed = JSON.parse(raw);
      return {
        user_id: parsed?.id ? Number(parsed.id) : null,
        username: parsed?.username || parsed?.user || '',
        user_fullname: parsed?.fullname || parsed?.name || parsed?.username || '',
      };
    }
  } catch (e) {
    // ignore invalid localStorage json
  }
  return { user_id: null, username: '', user_fullname: '' };
}

/**
 * Send an inventory audit/page-activity log event to the backend.
 * Failures are swallowed silently so logging never interrupts normal UI flows.
 */
export async function logInventoryEvent({
  action = 'VIEW_PAGE',
  page_code = 'overview',
  page_name = '',
  warehouse_id = undefined,
  warehouse_name = undefined,
  target_type = null,
  target_id = null,
  details = '',
  meta = null,
  dedupeMs = 0,
} = {}) {
  try {
    const token = localStorage.getItem('user_token');
    if (!token) return;

    const whCtx = readCurrentWarehouseContext();
    const userCtx = readCurrentUserContext();
    const resolvedWhId = warehouse_id !== undefined ? warehouse_id : whCtx.warehouse_id;
    const resolvedWhName = warehouse_name !== undefined ? warehouse_name : whCtx.warehouse_name;
    const resolvedPageName = page_name || INVENTORY_PAGE_NAMES[page_code] || page_code;

    if (dedupeMs > 0) {
      const dedupeKey = `${action}:${page_code}:${resolvedWhId ?? 'all'}:${target_type ?? ''}:${target_id ?? ''}`;
      const now = Date.now();
      const prev = recentEventTimestamps.get(dedupeKey) || 0;
      if (now - prev < dedupeMs) {
        return;
      }
      recentEventTimestamps.set(dedupeKey, now);
    }

    await axios.post(ACTIVITY_LOG_ENDPOINT, {
      action,
      page_code,
      page_name: resolvedPageName,
      warehouse_id: resolvedWhId,
      warehouse_name: resolvedWhName,
      user_id: userCtx.user_id,
      username: userCtx.username,
      user_fullname: userCtx.user_fullname,
      target_type,
      target_id,
      details: details || `เปิดหน้า ${resolvedPageName}`,
      meta: {
        path: typeof window !== 'undefined' ? window.location.pathname + window.location.search : '',
        ...(meta && typeof meta === 'object' ? meta : {}),
      },
    });
  } catch (e) {
    // Never block UI navigation or actions when logging fails
  }
}

/**
 * Log route-level visits for standalone /inventory-* routes and /inventory-dashboard?tab=...
 */
export function logInventoryRouteVisit(to) {
  if (!to || !to.path) return;
  const path = to.path;

  if (path === '/inventory-dashboard') {
    const tab = String(to.query?.tab || 'overview');
    const pageName = INVENTORY_PAGE_NAMES[tab] || tab;
    logInventoryEvent({
      action: 'VIEW_PAGE',
      page_code: tab,
      page_name: pageName,
      details: `เปิดแท็บ ${pageName} (Dashboard)`,
      meta: { via: 'route', fullPath: to.fullPath },
      dedupeMs: 2000,
    });
    return;
  }

  const mapped = ROUTE_PAGE_MAP[path];
  if (!mapped) return;

  const extraId = to.query?.id || to.query?.pr_id || to.query?.po_id || to.query?.req_id || null;
  logInventoryEvent({
    action: 'VIEW_PAGE',
    page_code: mapped.page_code,
    page_name: mapped.page_name,
    target_id: extraId ? String(extraId) : null,
    details: `เปิดหน้า ${mapped.page_name}${extraId ? ` (#${extraId})` : ''}`,
    meta: { via: 'route', fullPath: to.fullPath },
    dedupeMs: 2000,
  });
}
