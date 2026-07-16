import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/public/HomeView.vue';

const routes = [
  { path: '/', component: HomeView },
  { path: '/sla', component: () => import('../views/public/SlaView.vue') },
  { path: '/certificate', component: () => import('../views/public/CertificateView.vue') },
  { path: '/policy', component: () => import('../views/public/PolicyView.vue') },
  { path: '/document', component: () => import('../views/public/DocumentView.vue') },
  { path: '/pdpa', component: () => import('../views/public/PdpaView.vue') },
  { path: '/structure', component: () => import('../views/public/StructureView.vue') },
  { path: '/handbook', component: () => import('../views/public/HandbookView.vue') },
  { path: '/dashboard', component: () => import('../views/public/DashboardView.vue') },
  { path: '/schedule', component: () => import('../views/public/ScheduleView.vue') },
  { path: '/kpi', component: () => import('../views/public/KpiDashboardView.vue'), meta: { requiresAuth: true } },
  { path: '/asset-scan/:code(.*)', name: 'AssetScan', component: () => import('../views/asset/AssetScanView.vue') },

  // { path: "/communication", component: () => import("../views/communicationView.vue") },

  //Dashboard Report-Hosxe
  { path: '/dm-report', component: () => import('../views/reports/dm/DmReport.vue') },
  { path: '/er-report', component: () => import('../views/reports/er/ErReport.vue') },
  { path: '/dental-report', component: () => import('../views/reports/dental/DentalReport.vue') },
  { path: '/ttm-report', component: () => import('../views/reports/ttm/TTMReport.vue') },
  { path: '/physic-report', component: () => import('../views/reports/physic/PhysicReport.vue') },
  { path: '/telemedicine-report', component: () => import('../views/reports/telemedicine/TelemedicineReport.vue') },
  { path: '/ipd-report', component: () => import('../views/reports/ipd/IPDReport.vue') },
  {
    path: '/opddashboardchart',
    component: () => import('../views/reports/opd/OPDDashboardChart.vue')
  },
  { path: '/op-insurance', component: () => import('../views/reports/insurance/OpInsurance.vue') },

  //Digital
  { path: '/from-duty', component: () => import('../views/digital/FromDuty.vue') },
  { path: '/from-dutyopdcard', component: () => import('../views/digital/FromDutyOpdCard.vue') },
  { path: '/from-dutyclaim', component: () => import('../views/digital/FromDutyClaim.vue') },
  { path: '/schedule-opdcard', component: () => import('../views/digital/ScheduleOpdCard.vue') },
  { path: '/manager_duties_it', component: () => import('../views/digital/ManagerDuties_it.vue') },
  {
    path: '/manager_duties_opdcard',
    component: () => import('../views/digital/ManagerDutiesOpdcard.vue')
  },
  {
    path: '/manager_duties_claim',
    component: () => import('../views/digital/ManagerDutiesClaim.vue')
  },
  { path: '/ot-report-it', component: () => import('../views/reports/OTReportItView.vue'), meta: { requiresAuth: true } },
  { path: '/ot-report-opdcard', component: () => import('../views/reports/OTReportOpdCardView.vue'), meta: { requiresAuth: true } },
  { path: '/ot-report-claim', component: () => import('../views/reports/OTReportClaimView.vue'), meta: { requiresAuth: true } },
  { path: '/ot-report-summary', component: () => import('../views/reports/OTReportSummaryView.vue'), meta: { requiresAuth: true } },

  //KPI
  { path: '/kpi-setup', component: () => import('../views/kpi/KpiSetup.vue'), meta: { requiresAuth: true } },
  { path: '/kpi-result', component: () => import('../views/kpi/KpiResult.vue'), meta: { requiresAuth: true } },

  { path: '/login', component: () => import('../views/auth/LoginView.vue'), meta: { hideNavbar: true } },

  // --- Digital (โซนที่ต้องการป้องกัน) ---
  // Backoffice
  {
    path: '/home-backoffice',
    component: () => import('../views/backoffice/HomeBackofiice.vue'),
    meta: { requiresAuth: true } // ต้องล็อกอิน
  },
  {
    path: '/report',
    component: () => import('../views/backoffice/ReprotCenterView.vue'),
    meta: { requiresAuth: true } // ต้องล็อกอิน
  },
  {
    path: '/finger-scan',
    component: () => import('../views/backoffice/FingerScanView.vue'),
    meta: { requiresAuth: true } // ต้องล็อกอิน
  },
  {
    path: '/manager-schedule',
    component: () => import('../views/digital/ManagerSchedule.vue'),
    meta: { requiresAuth: true } // ต้องล็อกอิน
  },
  {
    path: '/user-manager',
    component: () => import('../views/backoffice/UserManagerView.vue'),
    meta: { requiresAuth: true } // ต้องล็อกอิน
  },
  {
    path: '/admin-docs',
    component: () => import('../views/backoffice/AdminDocumentCenter.vue'),
    meta: { requiresAuth: true } // ต้องล็อกอิน
  },
  // Git Sync
  {
    path: '/git-sync',
    component: () => import('../views/backoffice/GitSync.vue'),
    meta: { requiresAuth: true }
  },
  // Computer Repair
  {
    path: '/computer-repair',
    name: 'ComputerRepair',
    component: () => import('../views/backoffice/ComputerRepair.vue'),
    meta: { requiresAuth: true }
  },
  // Asset Management
  {
    path: '/asset-management',
    component: () => import('../views/asset/AssetDashboard.vue'),
    meta: { requiresAuth: true }
  },
  // IP Address Management
  {
    path: '/ip-address',
    name: 'IpAddressList',
    component: () => import('../views/digital/ip_address/IpAddressList.vue'),
    meta: { title: 'ทะเบียน IP Address - Digital' }
  },
  {
    path: '/server-list',
    name: 'ServerList',
    component: () => import('../views/digital/server/ServerList.vue'),
    meta: { title: 'ทะเบียน Server - Digital' }
  },
  // Software Management
  {
    path: '/software-dashboard',
    component: () => import('../views/digital/software/SoftwareDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/software-list',
    component: () => import('../views/digital/software/SoftwareList.vue'),
    meta: { requiresAuth: true }
  },

  // Revenue Collection Center
  {
    path: '/revenue-dashboard',
    component: () => import('../views/revenue/RevenueDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/revenue-setup',
    component: () => import('../views/revenue/RevenueTargetSetup.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/revenue-result',
    component: () => import('../views/revenue/RevenueResultReport.vue'),
    meta: { requiresAuth: true }
  },

  // Communication Channels Registry
  {
    path: '/communication-dashboard',
    component: () => import('../views/digital/communication/CommunicationDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/communication-list',
    component: () => import('../views/digital/communication/CommunicationList.vue'),
    meta: { requiresAuth: true }
  },

  // Material Management v2
  {
    path: '/material-v2',
    component: () => import('../views/digital/material_v2/MtDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/stock',
    component: () => import('../views/digital/material_v2/MtStock.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/in',
    component: () => import('../views/digital/material_v2/MtTransactionIn.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/out',
    component: () => import('../views/digital/material_v2/MtTransactionOut.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/report',
    component: () => import('../views/digital/material_v2/MtReport.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/monthly-report',
    component: () => import('../views/digital/material_v2/MtMonthlyReport.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/yearly-report',
    name: 'MtV2YearlyReport',
    component: () => import('../views/digital/material_v2/MtYearlyReport.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-request',
    component: () => import('../views/digital/material_v2/MtRequestForm.vue') // Public/Staff facing, could be unauthenticated depending on requirements, but let's keep consistent if needed. Let's make it public like helpdesk.
  },
  {
    path: '/material-v2/requests',
    component: () => import('../views/digital/material_v2/MtRequestsManage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/settings',
    component: () => import('../views/digital/material_v2/MtPrintSettings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-v2/request-print/:id',
    name: 'MtRequestPrint',
    component: () => import('../views/digital/material_v2/MtRequestPrint.vue'),
    meta: { requiresAuth: true, hideNavbar: true }
  },

  // General Material Management (Administration)
  {
    path: '/material-admin',
    component: () => import('../views/digital/material_admin/MtDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin/stock',
    component: () => import('../views/digital/material_admin/MtStock.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin/in',
    component: () => import('../views/digital/material_admin/MtTransactionIn.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin/out',
    component: () => import('../views/digital/material_admin/MtTransactionOut.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin/report',
    component: () => import('../views/digital/material_admin/MtReport.vue'),
    meta: { requiresAuth: true }
  },

  {
    path: '/material-admin/monthly-report',
    name: 'MtMonthlyReport',
    component: () => import('../views/digital/material_admin/MtMonthlyReport.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin/yearly-report',
    name: 'MtYearlyReport',
    component: () => import('../views/digital/material_admin/MtYearlyReport.vue'),
    meta: { requiresAuth: true }
  },

  {
    path: '/material-admin/settings',
    component: () => import('../views/digital/material_admin/MtPrintSettings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin-request',
    component: () => import('../views/digital/material_admin/MtRequestForm.vue')
  },
  {
    path: '/material-admin/requests',
    component: () => import('../views/digital/material_admin/MtRequestsManage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/material-admin/request-print/:id',
    name: 'MtAdminRequestPrint',
    component: () => import('../views/digital/material_admin/MtRequestPrint.vue'),
    meta: { requiresAuth: true, hideNavbar: true }
  },

  
  // Computer Loan
  {
    path: '/computer-loan',
    component: () => import('../views/digital/computer_loan/ComputerLoanForm.vue') // Public/Staff facing
  },
  {
    path: '/computer-loan/manage',
    component: () => import('../views/digital/computer_loan/ComputerLoanManage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/report-center',
    component: () => import('../views/backoffice/ReportCenter/ReportUser.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/report-center/admin',
    component: () => import('../views/backoffice/ReportCenter/ReportAdmin.vue'),
    meta: { requiresAuth: true }
  },
  
  // Procurement
  {
    path: '/procurement',
    component: () => import('../views/procurement/ProcurementDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/procurement/print/memo/:id',
    component: () => import('../views/procurement/PrintMemoView.vue'),
    meta: { requiresAuth: true, hideNavbar: true }
  },
  {
    path: '/procurement/print/winner/:id',
    component: () => import('../views/procurement/PrintWinnerView.vue'),
    meta: { requiresAuth: true, hideNavbar: true }
  },
  {
    path: '/procurement/print/po/:id',
    component: () => import('../views/procurement/PrintPOView.vue'),
    meta: { requiresAuth: true, hideNavbar: true }
  },
  {
    path: '/procurement/print/inspection/:id',
    component: () => import('../views/procurement/PrintInspectionView.vue'),
    meta: { requiresAuth: true, hideNavbar: true }
  },

  { path: '/:pathMatch(.*)*', component: () => import('../views/errors/Page404.vue') } // แก้ไขจาก pathMathch เป็น pathMatch
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// --- Navigation Guard ---
router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('user_token');
  const lastActivity = localStorage.getItem('last_activity');
  const TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes

  // 1. ถ้าหน้าที่จะไปมีการตั้งค่า requiresAuth ไว้ แต่ยังไม่ได้ Login
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login'); // ส่งไปหน้า Login
  }
  // 1.1 เช็ค Session Timeout สำหรับคนที่ Login แล้ว
  else if (isAuthenticated && lastActivity) {
    const now = Date.now();
    if (now - parseInt(lastActivity) > TIMEOUT_MS) {
      // หมดเวลา
      localStorage.removeItem('user_token');
      localStorage.removeItem('user_name');
      localStorage.removeItem('last_activity');
      next('/login');
    } else {
      // ยังไม่หมดเวลา -> อัพเดทเวลาล่าสุด
      localStorage.setItem('last_activity', now);

      // 2. ถ้า Login อยู่แล้ว และกำลังจะไปหน้า Login
      if (to.path === '/login') {
        next('/manager-schedule');
      } else {
        next();
      }
    }
  }
  // 2. ถ้า Login อยู่แล้ว และกำลังจะไปหน้า Login (กรณีไม่มี lastActivity หรือเคสอื่นๆ)
  else if (to.path === '/login' && isAuthenticated) {
    next('/manager-schedule'); // ดีดไปหน้าจัดการทันที
  }
  // 3. ปล่อยให้ไปหน้าอื่นๆ ได้ตามปกติ (เช่นหน้า Home หรือหน้าเปิดสาธารณะ)
  else {
    next();
  }
});

export default router;
