import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";

const routes = [
  { path: "/", component: HomeView },
  { path: "/sla", component: () => import("../views/SlaView.vue") },
  { path: "/certificate", component: () => import("../views/CertificateView.vue") },
  { path: "/policy", component: () => import("../views/PolicyView.vue") },
  { path: "/document", component: () => import("../views/DocumentView.vue") },
  { path: "/pdpa", component: () => import("../views/PdpaView.vue") },
  { path: "/structure", component: () => import("../views/StructureView.vue") },
  { path: "/handbook", component: () => import("../views/HandbookView.vue") },
  { path: "/handbook", component: () => import("../views/HandbookView.vue") },
  { path: "/dashboard", component: () => import("../views/DashboardView.vue") },
  { path: "/schedule", component: () => import("../views/ScheduleView.vue") },
 

//Dashboard Report 
  { path: "/dm-report", component: () => import("../views/reports/dm/DmReport.vue") },
  { path: "/er-report", component: () => import("../views/reports/er/ErReport.vue") },
  { path: "/dental-report", component: () => import("../views/reports/dental/DentalReport.vue") },
  { path: "/ttm-report", component: () => import("../views/reports/ttm/TTMReport.vue") },
  { path: "/physic-report", component: () => import("../views/reports/physic/PhysicReport.vue") },
  { path: "/ipd-report", component: () => import("../views/reports/ipd/IPDReport.vue") },
  { path: "/opddashboardchart", component: () => import("../views/reports/opd/OPDDashboardChart.vue") },

];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
