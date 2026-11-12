<template>
  <div class="container-fluid py-4 px-3 px-md-4 bg-light min-vh-100">
    <!-- Loading / Access Check -->
    <div v-if="isCheckingAccess" class="d-flex flex-column align-items-center justify-content-center py-5">
      <div class="spinner-border text-success mb-3" role="status"></div>
      <div class="text-muted">กำลังตรวจสอบสิทธิ์ผู้ดูแลระบบ...</div>
    </div>

    <!-- Main Admin Page -->
    <div v-else-if="isAuthorized" class="mx-auto" style="max-width: 1120px;">
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-4 bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px;">
              <i class="bi bi-bell-fill fs-3"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h4 fw-bold text-dark mb-0">ตั้งค่าคีย์ MOPH Notify</h1>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">เฉพาะ Administrator</span>
              </div>
              <p class="text-muted small mb-0 mt-1">
                จัดการ Client Key และ Secret Key สำหรับส่งแจ้งเตือนเข้า LINE โดยจัดเก็บในฐานข้อมูล <code>digital</code> แทนการเก็บไว้ในไฟล์โค้ด
              </p>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold" @click="$router.push('/manager-schedule')">
              <i class="bi bi-calendar-week me-1"></i> กลับหน้าจัดการตารางเวร
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold" @click="$router.push('/home-backoffice')">
              <i class="bi bi-house-door me-1"></i> กลับหน้าหลัก E-Office
            </button>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3 p-md-4">
          <MophNotifyKeyManager />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import MophNotifyKeyManager from '../../components/MophNotifyKeyManager.vue';

const router = useRouter();
const route = useRoute();

const isCheckingAccess = ref(true);
const isAuthorized = ref(false);

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
    await Swal.fire('ปฏิเสธการเข้าถึง', 'หน้าตั้งค่าคีย์ MOPH Notify สำหรับผู้ดูแลระบบ (Administrator) เท่านั้น', 'warning');
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
  verifyAdminAccess();
});
</script>

<style scoped>
.min-vh-100 {
  font-family: 'Sarabun', sans-serif;
}
</style>

