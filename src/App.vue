<template>
  <nav v-if="!route.meta.hideNavbar" class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <RouterLink class="navbar-brand d-flex align-items-center" to="/">
        <div class="logo-wrapper">
          <img src="./assets/logo-digital.jpg" alt="Logo" class="logo-img" />
        </div>
      </RouterLink>

      <button
        class="navbar-toggler custom-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item">
            <RouterLink class="nav-link" to="/dashboard">
              <i class="bi bi-speedometer2"></i> <span>DASHBOARD</span>
            </RouterLink>
          </li>

          <li class="nav-item">
            <RouterLink class="nav-link" to="/schedule">
              <i class="bi bi-calendar-week"></i> <span>ตารางเวร IT</span>
            </RouterLink>
          </li>

          <li class="nav-item">
            <RouterLink class="nav-link" to="/kpi">
              <i class="bi bi-speedometer2"></i> <span>KPI</span>
            </RouterLink>
          </li>

          <li class="nav-item dropdown" :class="{ show: activeDropdown === 'important' }">
            <a
              class="nav-link dropdown-toggle"
              href="#"
              id="importantLinks"
              role="button"
              @click.prevent="toggleDropdown('important')"
              aria-expanded="false"
            >
              <i class="bi bi-info-circle-fill"></i> <span>ข้อมูลสำคัญ</span>
            </a>
            <ul
              class="dropdown-menu dropdown-menu-end animate slideIn"
              :class="{ show: activeDropdown === 'important' }"
              aria-labelledby="importantLinks"
            >
              <li><h6 class="dropdown-header">เอกสาร & ระเบียบ</h6></li>
              <li v-for="cat in docCategories" :key="cat.category_key">
                <RouterLink class="dropdown-item" :to="`/docs/${cat.category_key}`">
                  <i class="bi bi-file-earmark-text"></i> {{ cat.category_name }}
                </RouterLink>
              </li>
              <li class="nav-item dropdown-submenu">
                <RouterLink class="dropdown-item dropdown-toggle" to="/structure">
                  <i class="bi bi-diagram-3"></i> ผังองค์กร
                </RouterLink>
                <ul class="dropdown-menu submenu-scroll">
                  <li>
                    <RouterLink class="dropdown-item bg-light fw-bold" to="/structure">
                      <i class="bi bi-grid"></i> แผนก/หน่วยงานย่อย (ทั้งหมด)
                    </RouterLink>
                  </li>
                  <li v-for="dept in orgMenu" :key="dept.id">
                    <RouterLink class="dropdown-item" :to="`/structure?dept_id=${dept.id}`">
                      {{ dept.name }}
                    </RouterLink>
                  </li>
                </ul>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li>
                <RouterLink class="dropdown-item" to="/handbook"
                  ><i class="bi bi-book"></i> คู่มือการใช้งาน</RouterLink
                >
              </li>
            </ul>
          </li>
          <li class="nav-item dropdown" :class="{ show: activeDropdown === 'ipd' }">
            <a
              class="nav-link dropdown-toggle d-flex align-items-center"
              href="#"
              id="mophDropdown"
              role="button"
              @click.prevent="toggleDropdown('ipd')"
              aria-expanded="false"
            >
              <i class="bi bi-link-45deg me-1"></i> IPDPaperless
            </a>

            <ul
              class="dropdown-menu"
              :class="{ show: activeDropdown === 'ipd' }"
              aria-labelledby="mophDropdown"
            >
              <li>
                <a class="dropdown-item" href="http://192.168.2.16/#/homePage2" target="_blank">
                  IPDNurse
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="http://192.168.2.16:5000/" target="_blank">
                  IPDDispensing
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <RouterLink class="nav-link nav-link-highlight" to="/home-backoffice">
              <i class="bi bi-person-gear"></i><span>Eoffice</span>
            </RouterLink>
          </li>

          <li class="nav-item dropdown" :class="{ show: activeDropdown === 'link' }">
            <a
              class="nav-link dropdown-toggle btn-link-center"
              href="#"
              id="linkCenter"
              role="button"
              @click.prevent="toggleDropdown('link')"
              aria-expanded="false"
            >
              <i class="bi bi-box-arrow-up-right"></i> <span>LINK-CENTER</span>
            </a>
            <ul
              class="dropdown-menu dropdown-menu-end mega-dropdown animate slideIn"
              :class="{ show: activeDropdown === 'link' }"
              aria-labelledby="linkCenter"
              style="overflow-y: auto; max-height: 80vh"
            >
              <li><h6 class="dropdown-header">MOPH Systems</h6></li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://cvp1.moph.go.th/accountcenter/"
                  target="_blank"
                  ><i class="bi bi-person-circle"></i> Moph Account</a
                >
              </li>

              <li>
                <a class="dropdown-item" href="https://phr1.moph.go.th/dashboard/" target="_blank"
                  ><i class="bi bi-clipboard-data"></i> Moph PHR-Dashboard</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://cvp1.moph.go.th/appointment/" target="_blank"
                  ><i class="bi bi-calendar-event"></i> Moph Appointment</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://moph-appointment-cms.moph.go.th/login"
                  target="_blank"
                  ><i class="bi bi-calendar-event"></i> Moph Appointment CMS</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://mohpromtstation.moph.go.th/" target="_blank"
                  ><i class="bi bi-capsule"></i> หมอพร้อม Station</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://moph-phr.bmscloud.in.th/dashboard"
                  target="_blank"
                >
                  <i class="bi bi-file-medical fa-fw me-2"></i> Moph PHR-Viwer
                </a>
              </li>

              <li>
                <a class="dropdown-item" href="https://imh.moph.go.th/img/ " target="_blank">
                  <i class="bi bi-images fa-fw me-2"></i>ส่งภาพ Imaginghub
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://imaginghub-dashboard.one.th/board_view"
                  target="_blank"
                >
                  <i class="bi bi-speedometer fa-fw me-2"></i> Imaginghub Dashboard
                </a>
              </li>

              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">Claim Systems</h6></li>
              <li>
                <a class="dropdown-item" href="https://claim-nhso.moph.go.th/nhso/" target="_blank"
                  ><i class="bi bi-currency-dollar"></i> Moph Claim</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://fdh.moph.go.th/hospital/" target="_blank"
                  ><i class="bi bi-database"></i> FDH Hub</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://eclaim.nhso.go.th/Client/login?url=%2Fhome"
                  target="_blank"
                >
                  <i class="bi bi-receipt fa-fw me-2"></i>
                  E-CLAIM
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://www.rvp-eclaim.com/index.html"
                  target="_blank"
                >
                  <i class="bi bi-receipt-cutoff fa-fw me-2"></i>
                  CLAIM พรบ.
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://www.bizgrowing.krungthai.com/corporate/"
                  target="_blank"
                >
                  <i class="bi bi-bank fa-fw me-2"></i>
                  CLAIM กรุงไทย
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://eclaim.nhso.go.th/Client/login"
                  target="_blank"
                >
                  <i class="bi bi-archive fa-fw me-2"></i>
                  E-CLAIM ระบบเดิม
                </a>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบพิสูจน์ตัวตน</h6></li>

              <li>
                <a class="dropdown-item" href="https://phr1.moph.go.th/idpadmin/" target="_blank">
                  <i class="bi bi-person-badge fa-fw me-2"></i> MOPH IDP Center (ekyc)
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="https://provider.id.th/" target="_blank">
                  <i class="bi bi-person-vcard fa-fw me-2"></i> Provider ID
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="https://moph.id.th/login" target="_blank">
                  <i class="bi bi-person-heart fa-fw me-2"></i> Health ID
                </a>
              </li>

              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบบริการ</h6></li>
              <li>
                <a class="dropdown-item" href="https://moph-refer.inet.co.th/" target="_blank">
                  <i class="bi bi-arrow-left-right fa-fw me-2"></i> Moph Refer
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="http://192.168.2.16:8088" target="_blank">
                  <i class="bi bi-hospital fa-fw me-2"></i> Smart Refer
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="http://192.168.2.16:8082" target="_blank">
                  <i class="bi bi-hospital fa-fw me-2"></i> Smart LR
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://connect.moph.go.th/pher-plus/#/login"
                  target="_blank"
                >
                  <i class="bi bi-activity fa-fw me-2"></i> PHER Plus
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="https://cnmh.thai-nrls.org/" target="_blank">
                  <i class="bi bi-hdd-network fa-fw me-2"></i>
                  โปรแกรมความเสี่ยง
                </a>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบประเมิน</h6></li>

              <li>
                <a
                  class="dropdown-item"
                  href="https://bdh-service.moph.go.th/smarthosp2569/"
                  target="_blank"
                  ><i class="bi bi-clipboard-check fa-fw me-2"></i> ระบบประเมิน รพ.อัจฉริยะ 2569
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://ctam.moph.go.th/public/reports"
                  target="_blank"
                  ><i class="bi bi-clipboard-check fa-fw me-2"></i> CTAM+
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="https://hs4.hss.moph.go.th/login" target="_blank"
                  ><i class="bi bi-clipboard-check fa-fw me-2"></i>
                  ระบบประเมินมาตรฐานระบบบริการสุขภาพ HS4
                </a>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบอื่นๆ</h6></li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://hdc.moph.go.th/acr/public/login"
                  target="_blank"
                  ><i class="bi bi-clipboard-data fa-fw me-2"></i>HDC อำนาจเจริญ</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://apps-amno.moph.go.th/hdcamnat/"
                  target="_blank"
                  ><i class="bi bi-clipboard-data fa-fw me-2"></i>HDA อำนาจเจริญ</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://learning.kaorag.com/login" target="_blank"
                  ><i class="bi bi-people fa-fw me-2"></i>พลเมืองดิจิทัล</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://buddy-care.org" target="_blank"
                  ><i class="bi bi-heart-pulse fa-fw me-2"></i>สอน.บัดดี้</a
                >
              </li>

              <li>
                <a class="dropdown-item" href="https://gppc-app.pdpc.or.th/signin" target="_blank"
                  ><i class="bi bi-shield-lock fa-fw me-2"></i>GPPC</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://tdga.dga.or.th/" target="_blank"
                  ><i class="bi bi-building fa-fw me-2"></i>TDGA</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://mcs.inet.co.th/" target="_blank"
                  ><i class="bi bi-shield-shaded fa-fw me-2"></i>CSOC</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://play.mooc.ncsa.or.th/login?src=timeout"
                  target="_blank"
                  ><i class="bi bi-mortarboard fa-fw me-2"></i>MOOC</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://hosbox.id.th/" target="_blank"
                  ><i class="bi bi-mortarboard fa-fw me-2"></i>ONE-BOX</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://hscs.ha.or.th/68/login.php" target="_blank"
                  ><i class="bi bi-clipboard-pulse fa-fw me-2"></i
                  >ระบบสำรวจวัฒนธรรมความปลอดภัยของโรงพยาบาล</a
                >
              </li>
              <li>
                <a class="dropdown-item" href="https://workspace.moph.go.th/" target="_blank"
                  ><i class="bi bi-grid-3x3-gap fa-fw me-2"></i>Workspace MOPH</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://eoffice-chanuman.moph.go.th/api/auth/login"
                  target="_blank"
                  ><i class="bi bi-grid-3x3-gap fa-fw me-2"></i>ระบบสารบรรณ Eoffice Moph</a
                >
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <main class="content-area">
    <RouterView />
  </main>
  <AppFooter v-if="!route.meta.hideNavbar" />
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppFooter from './components/AppFooter.vue';

const router = useRouter();
const route = useRoute();

const TIMEOUT_MS = 30 * 60 * 1000;
let timeoutInterval = null;

// =========================
// STATE
// =========================
const orgMenu = ref([]);
const docCategories = ref([]);
const activeDropdown = ref(null);
const activeSubmenu = ref(null);

// =========================
// DROPDOWN
// =========================
const toggleDropdown = (id) => {
  activeDropdown.value = activeDropdown.value === id ? null : id;
};

const toggleSubmenu = (id) => {
  activeSubmenu.value = activeSubmenu.value === id ? null : id;
};

const closeDropdown = (e) => {
  if (!e.target.closest('.dropdown')) {
    activeDropdown.value = null;
    activeSubmenu.value = null;
  }
};

// =========================
// SESSION TIMEOUT
// =========================
const checkTimeout = () => {
  const token = localStorage.getItem('user_token');
  const lastActivity = localStorage.getItem('last_activity');

  if (token && lastActivity) {
    const now = Date.now();

    if (now - parseInt(lastActivity) > TIMEOUT_MS) {
      localStorage.removeItem('user_token');
      localStorage.removeItem('user_name');
      localStorage.removeItem('last_activity');

      router.push('/login');
    }
  }
};

const updateActivity = () => {
  const token = localStorage.getItem('user_token');
  const lastActivity = localStorage.getItem('last_activity');

  if (token && lastActivity) {
    const now = Date.now();

    if (now - parseInt(lastActivity) > TIMEOUT_MS) {
      checkTimeout();
      return;
    }

    localStorage.setItem('last_activity', now);
  }
};

// =========================
// DEBOUNCE
// =========================
let activityTimeout = null;

const updateActivityDebounced = () => {
  clearTimeout(activityTimeout);

  activityTimeout = setTimeout(() => {
    updateActivity();
  }, 300);
};

// =========================
// SYNC LOGOUT BETWEEN TABS
// =========================
const syncLogout = (e) => {
  if (e.key === 'user_token' && !e.newValue) {
    router.push('/login');
  }
};

// =========================
// ROUTE CHANGE
// =========================
router.afterEach(() => {
  activeDropdown.value = null;
  activeSubmenu.value = null;
});

// =========================
// MOUNT
// =========================
onMounted(async () => {
  // Activity Events
  window.addEventListener('mousemove', updateActivityDebounced);
  window.addEventListener('click', updateActivityDebounced);
  window.addEventListener('keydown', updateActivityDebounced);
  window.addEventListener('scroll', updateActivityDebounced);

  // Dropdown Close
  window.addEventListener('click', closeDropdown);

  // Sync Logout
  window.addEventListener('storage', syncLogout);

  // Fetch Org Menu
  try {
    const res = await fetch('/api-hosoffice/get_org_menu.php');

    if (!res.ok) {
      throw new Error('Network Error');
    }

    const data = await res.json();

    if (data.status === 'success') {
      orgMenu.value = data.data;
    }
  } catch (err) {
    console.error('Failed to fetch org menu:', err);
  }

  // Fetch Doc Categories
  try {
    const res = await fetch('/backend/api-digital/document_center/get_categories.php');
    if (res.ok) {
      const data = await res.json();
      if (data.status === 'success') {
        docCategories.value = data.data;
      }
    }
  } catch (err) {
    console.error('Failed to fetch doc categories:', err);
  }

  // Check timeout every minute
  timeoutInterval = setInterval(checkTimeout, 60 * 1000);
});

// =========================
// UNMOUNT
// =========================
onUnmounted(() => {
  window.removeEventListener('mousemove', updateActivityDebounced);
  window.removeEventListener('click', updateActivityDebounced);
  window.removeEventListener('keydown', updateActivityDebounced);
  window.removeEventListener('scroll', updateActivityDebounced);

  window.removeEventListener('click', closeDropdown);
  window.removeEventListener('storage', syncLogout);

  if (timeoutInterval) {
    clearInterval(timeoutInterval);
  }

  if (activityTimeout) {
    clearTimeout(activityTimeout);
  }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

* {
  font-family: 'Kanit', sans-serif;
}

/* =========================
   NAVBAR
========================= */
.navbar {
  background: linear-gradient(135deg, #6f42c1 0%, #4a2d81 100%);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  padding: 0.65rem 0;
  transition: all 0.3s ease;
  z-index: 1050;
}

/* =========================
   LOGO
========================= */
.logo-wrapper {
  background: white;
  padding: 4px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.logo-img {
  height: clamp(38px, 5vw, 45px);
  width: auto;
  border-radius: 10px;
}

/* =========================
   NAV LINK
========================= */
.nav-link {
  color: rgba(255, 255, 255, 0.88) !important;
  font-weight: 500;
  font-size: 0.95rem;
  padding: 0.65rem 1rem !important;
  margin: 0 2px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  transition: all 0.25s ease;
}

.nav-link i {
  font-size: 1rem;
  margin-right: 8px;
  width: 20px;
  text-align: center;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white !important;
  transform: translateY(-1px);
}

.nav-link.router-link-active,
.active-link {
  background: rgba(0, 0, 0, 0.15);
  color: white !important;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
}

/* =========================
   HIGHLIGHT LINK
========================= */
.nav-link-highlight {
  background: white !important;
  color: #4a2d81 !important;
  font-weight: 700 !important;
  border: 2px solid white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.nav-link-highlight i {
  color: #4a2d81 !important;
}

.nav-link-highlight:hover {
  background: transparent !important;
  color: white !important;
}

.nav-link-highlight:hover i {
  color: white !important;
}

/* =========================
   DROPDOWN
========================= */
.dropdown-menu {
  border: none;
  border-radius: 18px;
  padding: 0.8rem;
  min-width: 260px;
  margin-top: 12px !important;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  overflow-x: hidden;
  overflow-y: auto;
}

/* Mega Dropdown (Link-Center) */
.mega-dropdown {
  min-width: 320px;
  max-width: 360px;
  max-height: 80vh;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: #c5b3e6 transparent;
}

.mega-dropdown::-webkit-scrollbar {
  width: 5px;
}

.mega-dropdown::-webkit-scrollbar-track {
  background: transparent;
}

.mega-dropdown::-webkit-scrollbar-thumb {
  background: #c5b3e6;
  border-radius: 10px;
}

.dropdown-item {
  border-radius: 10px;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  transition: all 0.2s ease;
  color: #444;
  font-size: 0.92rem;
}

.dropdown-item i {
  width: 24px;
  margin-right: 10px;
  color: #6f42c1;
}

.dropdown-item:hover {
  background: #f3f0ff;
  color: #6f42c1;
  transform: translateX(4px);
}

.dropdown-header {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #999;
  padding-left: 1rem;
}

/* =========================
   SUBMENU
========================= */
.dropdown-submenu {
  position: relative;
}

.dropdown-submenu .dropdown-menu {
  top: 0;
  left: 100%;
  margin-left: 0.5rem;
  display: none;
  z-index: 1060;
}

.dropdown-submenu .dropdown-menu.show {
  display: block;
}

.submenu-scroll {
  max-height: 400px;
  overflow-y: auto;
  min-width: 280px;
}

/* =========================
   BUTTON LINK
========================= */
.btn-link-center {
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  margin-left: 10px !important;
}

/* =========================
   ANIMATION
========================= */
.animate {
  animation-duration: 0.3s;
  animation-fill-mode: both;
}

.slideIn {
  animation-name: slideIn;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* =========================
   MOBILE
========================= */
@media (max-width: 991.98px) {
  .navbar-collapse {
    background: white;
    margin-top: 15px;
    padding: 20px;
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
    transition: all 0.3s ease;
  }

  .nav-link {
    justify-content: flex-start;
    padding: 12px 18px !important;
    color: #333 !important;
  }

  .nav-link i {
    color: #6f42c1 !important;
  }

  .nav-link:hover {
    background: #f3f0ff;
    color: #6f42c1 !important;
  }

  .btn-link-center {
    margin-left: 0 !important;
    margin-top: 10px;
  }

  .dropdown-submenu .dropdown-menu {
    position: static;
    margin-left: 0;
    margin-top: 10px;
    box-shadow: none;
    border: 1px solid #eee;
  }

  .submenu-scroll {
    max-height: 300px;
  }
}

/* =========================
   TOGGLER
========================= */
.custom-toggler {
  border: none;
  box-shadow: none !important;
}

/* =========================
   CONTENT
========================= */
.content-area {
  min-height: calc(100vh - 70px);
  padding-top: 80px; /* Space for the fixed-top navbar */
}

/* =========================
   PDF EXPORT
========================= */
:global(body.pdf-export-mode footer),
:global(body.pdf-export-mode .footer),
:global(body.pdf-export-mode .AppFooter) {
  display: none !important;
  visibility: hidden !important;
}

/* =========================
   PRINT
========================= */
@media print {
  nav,
  .navbar,
  footer,
  .footer,
  .AppFooter {
    display: none !important;
  }
}
</style>
