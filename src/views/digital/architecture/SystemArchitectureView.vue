<template>
  <div v-if="isCheckingAccess" class="arch-page-wrapper container-fluid py-5 d-flex flex-column align-items-center justify-content-center">
    <div class="spinner-border text-primary mb-2" role="status"></div>
    <div class="text-muted small">กำลังตรวจสอบสิทธิ์ผู้ดูแลระบบ...</div>
  </div>
  <div v-else-if="isAuthorized" class="arch-page-wrapper container-fluid py-3 px-3 px-lg-4">
    <!-- Top Header Card -->
    <div class="arch-header-card rounded-4 shadow-sm p-3 p-md-4 mb-3 text-white position-relative overflow-hidden">
      <div class="arch-header-glow"></div>
      <div class="position-relative z-1 d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
        <div class="d-flex align-items-start align-items-md-center gap-3">
          <div class="arch-icon-badge rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
            <i :class="`bi ${activeDiagram.icon} fs-3`"></i>
          </div>
          <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <h4 class="fw-bold mb-0">{{ activeDiagram.title }}</h4>
              <span class="badge rounded-pill bg-success bg-opacity-75 border border-light border-opacity-25">
                <i class="bi bi-stars me-1"></i>Archify Interactive ({{ diagrams.length }} แผนภาพ)
              </span>
            </div>
            <p class="text-white-50 small mb-2">
              {{ activeDiagram.description }}
            </p>
            <div class="d-flex flex-wrap gap-1">
              <span v-for="tag in activeDiagram.tags" :key="tag" class="badge arch-pill">
                {{ tag }}
              </span>
            </div>
          </div>
        </div>

        <!-- Action Controls -->
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
          <button
            type="button"
            class="btn btn-sm btn-outline-light rounded-pill px-3 d-inline-flex align-items-center gap-1"
            @click="reloadDiagram"
            title="โหลดแผนภาพใหม่"
          >
            <i class="bi bi-arrow-clockwise"></i>
            <span>รีโหลด</span>
          </button>

          <button
            type="button"
            class="btn btn-sm btn-light text-dark fw-semibold rounded-pill px-3 d-inline-flex align-items-center gap-1"
            @click="toggleFullscreen"
          >
            <i :class="isFullscreen ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen'"></i>
            <span>{{ isFullscreen ? 'ออกจากเต็มหน้าจอ' : 'เต็มหน้าจอ' }}</span>
          </button>

          <a
            :href="diagramUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-sm btn-info text-dark fw-semibold rounded-pill px-3 d-inline-flex align-items-center gap-1"
          >
            <i class="bi bi-box-arrow-up-right"></i>
            <span>เปิดแท็บใหม่</span>
          </a>

          <a
            :href="diagramUrl"
            :download="`vite-digital-${activeDiagram.file}`"
            class="btn btn-sm btn-success fw-semibold rounded-pill px-3 d-inline-flex align-items-center gap-1"
          >
            <i class="bi bi-download"></i>
            <span>ดาวน์โหลด HTML</span>
          </a>
        </div>
      </div>

      <!-- Diagram Selector Tabs -->
      <div class="position-relative z-1 mt-3 pt-3 border-top border-light border-opacity-10">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="small text-white-50 fw-semibold me-1">
            <i class="bi bi-layers me-1 text-info"></i>เลือกดูแผนภาพ:
          </span>
          <button
            v-for="item in diagrams"
            :key="item.id"
            type="button"
            class="btn btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-2 arch-tab-btn"
            :class="selectedId === item.id ? 'arch-tab-active fw-bold shadow-sm' : 'arch-tab-inactive'"
            @click="selectDiagram(item.id)"
          >
            <i :class="`bi ${item.icon}`"></i>
            <span>{{ item.shortLabel }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Embedded Archify Viewer -->
    <div
      ref="viewerContainerRef"
      class="arch-viewer-card card border-0 rounded-4 shadow-sm overflow-hidden position-relative"
      :class="{ 'arch-viewer-expanded': isExpandedFallback }"
    >
      <div
        v-if="isExpandedFallback"
        class="arch-fallback-bar d-flex align-items-center justify-content-between px-3 py-2 bg-dark text-white"
      >
        <span class="small fw-semibold">
          <i :class="`bi ${activeDiagram.icon} text-info me-2`"></i>{{ activeDiagram.title }} (โหมดเต็มหน้าจอ)
        </span>
        <button
          type="button"
          class="btn btn-sm btn-outline-light rounded-pill px-3"
          @click="isExpandedFallback = false"
        >
          <i class="bi bi-x-lg me-1"></i>ปิดเต็มหน้าจอ
        </button>
      </div>

      <div
        v-if="isLoading"
        class="arch-loading-overlay d-flex flex-column align-items-center justify-content-center"
      >
        <div class="spinner-border text-primary mb-2" role="status"></div>
        <div class="text-muted small">กำลังโหลดแผนภาพ {{ activeDiagram.shortLabel }}...</div>
      </div>

      <iframe
        ref="diagramFrameRef"
        :key="frameKey"
        :src="diagramUrl"
        :title="activeDiagram.title"
        class="arch-iframe w-100 border-0 d-block"
        allow="fullscreen"
        @load="handleIframeLoad"
      ></iframe>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 px-1 text-muted small">
      <div>
        <i class="bi bi-info-circle me-1 text-primary"></i>
        ใช้แถบด้านบนภายในแผนภาพเพื่อสลับมุมมองย่อย (Views), ค้นหาหน้าจอ/Node, ซูมเข้า-ออก หรือส่งออก PNG / SVG / WebM (เมนูควบคุมของ Viewer เป็นภาษาอังกฤษ)
      </div>
      <div>
        <RouterLink to="/handbook" class="text-decoration-none me-3">
          <i class="bi bi-book me-1"></i>คู่มือระบบ
        </RouterLink>
        <RouterLink to="/home-backoffice" class="text-decoration-none">
          <i class="bi bi-grid me-1"></i>กลับหน้า E-Office
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

const isCheckingAccess = ref(true);
const isAuthorized = ref(false);

const diagrams = [
  {
    id: 'system',
    file: 'system-architecture.html',
    icon: 'bi-diagram-3-fill',
    shortLabel: '1. สถาปัตยกรรมทั้งระบบ (3-Tier)',
    title: 'สถาปัตยกรรมทั้งระบบ (Vite Digital 3-Tier Architecture)',
    description:
      'แผนภาพโครงสร้างและการไหลของข้อมูลทั้งระบบในโปรเจ็ค vite-digital ตั้งแต่ Vue 3 SPA, Auth & OTP Gateway, PHP 8 REST API จนถึงฐานข้อมูล MySQL 3 ก้อนและ Gemini AI OCR',
    tags: ['Vue 3 + Vite 5', 'PHP 8 API Gateway', 'MySQL (hosxe / hosoffice / digital_db)', 'Bearer + OTP Auth', 'Gemini AI OCR']
  },
  {
    id: 'inventory',
    file: 'pages-inventory.html',
    icon: 'bi-box-seam-fill',
    shortLabel: '2. หน้าเชื่อมโยง: คลังพัสดุและยา',
    title: 'แผนผังหน้าเชื่อมโยง: ระบบคลังพัสดุและเวชภัณฑ์รวมศูนย์',
    description:
      'แสดงการเชื่อมโยงทุกหน้าจอในวงจรคลัง 7 ขั้นตอน ตั้งแต่เลือกคลังใน E-Office เข้า Inventory Dashboard -> PR -> PO -> รับเข้า (AI OCR) -> ขอเบิก/FEFO -> โอน/คืน -> Stock Card & รายงาน',
    tags: ['/inventory-dashboard', 'PR <-> PO <-> Receive', 'Requisition & FEFO', 'Transfer & Return', 'Master 3 ตารางยา']
  },
  {
    id: 'materials',
    file: 'pages-materials.html',
    icon: 'bi-boxes',
    shortLabel: '3. หน้าเชื่อมโยง: วัสดุ IT / บริหาร',
    title: 'แผนผังหน้าเชื่อมโยง: ระบบวัสดุคอมพิวเตอร์ IT และวัสดุงานบริหาร',
    description:
      'แสดงหน้าจอขอเบิกสาธารณะ (/material-request, /material-admin-request) เชื่อมสู่หน้าอนุมัติ พิมพ์ใบเบิก รับเข้า ตัดจ่าย สต็อก และรายงานทั้งฝั่ง IT และงานบริหาร',
    tags: ['/material-request', '/material-it/*', '/material-admin-request', '/material-admin/*', 'Request Print (:id)']
  },
  {
    id: 'portal',
    file: 'pages-portal-clinical.html',
    icon: 'bi-hospital-fill',
    shortLabel: '4. หน้าเชื่อมโยง: สาธารณะ & คลินิก',
    title: 'แผนผังหน้าเชื่อมโยง: ระบบหน้าหลักสาธารณะ ปฏิทิน เอกสาร และรายงานคลินิก',
    description:
      'แสดงเส้นทางเมนูจาก Top Navbar (App.vue), หน้าแรก, ปฏิทิน, ผังองค์กร, HAD/IPDPaperless และ Dashboard ผู้มารับบริการที่เชื่อมตรงสู่หน้ารายงานคลินิก 7 แผนก',
    tags: ['Navbar (App.vue)', '/dashboard -> 7 คลินิก', '/schedule -> IT Admin', '/kpi -> /kpi-setup', '/had-dashboard']
  },
  {
    id: 'it-schedule',
    file: 'pages-it-schedule.html',
    icon: 'bi-tools',
    shortLabel: '5. หน้าเชื่อมโยง: บริการ IT & ตารางเวร-OT',
    title: 'แผนผังหน้าเชื่อมโยง: ระบบบริการ IT, ครุภัณฑ์ และระบบตารางเวร-OT',
    description:
      'แสดงการเชื่อมโยงงานบริการ IT (ครุภัณฑ์ QR Scan -> แจ้งซ่อม ?asset_code, ยืม-คืน, ซอฟต์แวร์, ปริ้นเตอร์, IP/Server) และระบบตารางเวร ManagerSchedule -> เพิ่ม/แก้ไขเวร -> รายงาน OT',
    tags: ['/asset-management -> /computer-repair', '/computer-loan', '/software-list', '/printer-dashboard', '/manager-schedule -> OT Reports']
  },
  {
    id: 'eoffice',
    file: 'pages-eoffice-ops.html',
    icon: 'bi-building-gear',
    shortLabel: '6. หน้าเชื่อมโยง: E-Office, KPI, รายได้ & จัดซื้อ',
    title: 'แผนผังหน้าเชื่อมโยง: ระบบ E-Office, KPI/HA, จัดเก็บรายได้, รายงาน และจัดซื้อ',
    description:
      'แสดงเส้นทางหลัง Login เข้า HomeBackofiice เชื่อมต่อไปยังกลุ่ม KPI/HA, ศูนย์จัดเก็บรายได้, ศูนย์ขอข้อมูลและ Report Center (?report_id), จัดซื้อจัดจ้าง (พิมพ์ 4 แบบ) และสื่อสาร',
    tags: ['/login -> /home-backoffice', 'KPI & QI HA', '/revenue-*', '/report -> /report-center', '/procurement/print/*']
  }
];

const validIds = new Set(diagrams.map((d) => d.id));
const initialId = typeof route.query.view === 'string' && validIds.has(route.query.view) ? route.query.view : 'system';

const selectedId = ref(initialId);
const viewerContainerRef = ref(null);
const diagramFrameRef = ref(null);
const isLoading = ref(true);
const isFullscreen = ref(false);
const isExpandedFallback = ref(false);
const frameKey = ref(0);

const activeDiagram = computed(() => diagrams.find((d) => d.id === selectedId.value) || diagrams[0]);

/**
 * Resolves the static Archify HTML artifact across Vite dev server,
 * root/subpath web hosting, and Electron file:// bundle environments.
 */
const diagramUrl = computed(() => {
  const file = activeDiagram.value.file;
  if (import.meta.env.DEV) {
    return `/archify/${file}`;
  }
  try {
    const chunkUrl = new URL(import.meta.url);
    if (chunkUrl.pathname.includes('/assets/')) {
      return new URL(`../archify/${file}`, chunkUrl).href;
    }
  } catch {
    // Fall through to protocol/origin fallback below
  }
  return window.location.protocol === 'file:'
    ? `./archify/${file}`
    : `/archify/${file}`;
});

const selectDiagram = (id) => {
  if (!validIds.has(id) || selectedId.value === id) return;
  selectedId.value = id;
  isLoading.value = true;
  frameKey.value += 1;
  router.replace({ query: { ...route.query, view: id } }).catch(() => {});
};

watch(
  () => route.query.view,
  (newView) => {
    if (typeof newView === 'string' && validIds.has(newView) && newView !== selectedId.value) {
      selectedId.value = newView;
      isLoading.value = true;
      frameKey.value += 1;
    }
  }
);

const handleIframeLoad = () => {
  isLoading.value = false;
};

const reloadDiagram = () => {
  isLoading.value = true;
  frameKey.value += 1;
};

const handleFullscreenChange = () => {
  isFullscreen.value = Boolean(document.fullscreenElement) || isExpandedFallback.value;
};

const toggleFullscreen = async () => {
  if (isExpandedFallback.value) {
    isExpandedFallback.value = false;
    isFullscreen.value = false;
    return;
  }

  const el = viewerContainerRef.value;
  if (!el) return;

  try {
    if (!document.fullscreenElement) {
      if (el.requestFullscreen) {
        await el.requestFullscreen();
      } else {
        isExpandedFallback.value = true;
        isFullscreen.value = true;
      }
    } else if (document.exitFullscreen) {
      await document.exitFullscreen();
    }
  } catch {
    // Fallback to fixed viewport expansion if browser blocks fullscreen API
    isExpandedFallback.value = !isExpandedFallback.value;
    isFullscreen.value = isExpandedFallback.value;
  }
};

const verifyAdminAccess = async () => {
  const token = localStorage.getItem('user_token');
  if (!token) {
    isCheckingAccess.value = false;
    router.replace({ path: '/login', query: { redirect: route.fullPath } });
    return;
  }

  try {
    const response = await axios.get('/api-hosoffice/get_user_profile.php', {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (response.data && response.data.status === 'success') {
      const userPerms = response.data.access_user ? response.data.access_user.split(':') : [];
      if (userPerms.includes('administrator')) {
        isAuthorized.value = true;
        isCheckingAccess.value = false;
        return;
      }
    }
    isCheckingAccess.value = false;
    await Swal.fire('ปฏิเสธการเข้าถึง', 'เมนูนี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น', 'warning');
    router.replace('/home-backoffice');
  } catch (err) {
    console.error('Failed to verify admin access:', err);
    isCheckingAccess.value = false;
    if (err?.response?.status === 401) {
      localStorage.removeItem('user_token');
      localStorage.removeItem('user_name');
      localStorage.removeItem('last_activity');
      router.replace({ path: '/login', query: { redirect: route.fullPath } });
      return;
    }
    await Swal.fire('ปฏิเสธการเข้าถึง', 'ไม่สามารถตรวจสอบสิทธิ์ผู้ดูแลระบบได้', 'warning');
    router.replace('/home-backoffice');
  }
};

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange);
  verifyAdminAccess();
});

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<style scoped>
.arch-page-wrapper {
  min-height: calc(100vh - 80px);
}

.arch-header-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #0f766e 100%);
  border: 1px solid rgba(148, 163, 184, 0.2);
}

.arch-header-glow {
  position: absolute;
  top: -60px;
  right: -60px;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
  pointer-events: none;
}

.arch-icon-badge {
  width: 52px;
  height: 52px;
  background: rgba(56, 189, 248, 0.18);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8;
}

.arch-pill {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #e2e8f0;
  font-weight: 500;
  font-size: 0.72rem;
}

.arch-tab-btn {
  transition: all 0.2s ease;
  font-size: 0.8rem;
}

.arch-tab-active {
  background: #38bdf8;
  color: #0f172a;
  border: 1px solid #7dd3fc;
}

.arch-tab-active:hover {
  background: #7dd3fc;
  color: #0f172a;
}

.arch-tab-inactive {
  background: rgba(255, 255, 255, 0.08);
  color: #e2e8f0;
  border: 1px solid rgba(255, 255, 255, 0.18);
}

.arch-tab-inactive:hover {
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.arch-viewer-card {
  background: #0b1120;
  border: 1px solid rgba(148, 163, 184, 0.25) !important;
}

.arch-iframe {
  height: calc(100vh - 285px);
  min-height: 620px;
  background: #0b1120;
}

.arch-viewer-expanded {
  position: fixed !important;
  inset: 0;
  z-index: 1080;
  border-radius: 0 !important;
}

.arch-viewer-expanded .arch-iframe {
  height: calc(100vh - 44px);
}

.arch-loading-overlay {
  position: absolute;
  inset: 0;
  z-index: 5;
  background: rgba(248, 250, 252, 0.92);
}
</style>
