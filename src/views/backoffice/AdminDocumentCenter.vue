<template>
  <div class="page-container min-vh-100 bg-light py-5">
    <div class="container">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                  <li class="breadcrumb-item">
                    <router-link to="/home-backoffice">หน้าหลัก</router-link>
                  </li>
                  <li class="breadcrumb-item active" aria-current="page">จัดการเอกสาร</li>
                </ol>
              </nav>
              <h2 class="fw-bold text-dark mb-0">
                <i class="bi bi-files me-2 text-primary"></i>ศูนย์จัดการเอกสาร
              </h2>
            </div>
            <button class="btn btn-outline-secondary" @click="$router.push('/home-backoffice')">
              <i class="bi bi-arrow-left me-1"></i> กลับ
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4">
          <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4">
              <label class="form-label fw-bold">เลือกหมวดหมู่เอกสาร</label>
              <select
                v-model="selectedCategory"
                class="form-select form-select-lg shadow-sm border-0 bg-light"
              >
                <option value="document">ข่าวประกาศ (News & Announcements)</option>
                <option value="policy">นโยบาย (Policy)</option>
                <option value="pdpa">PDPA</option>
                <option value="sla">SLA</option>
                <option value="handbook">คู่มือการใช้งาน (Handbook)</option>
                <option value="certificate">ใบประกาศนียบัตร (Certificate)</option>
                <option value="communication">ช่องทางการสื่อสาร (Communication)</option>
              </select>
            </div>
          </div>

          <div class="document-area bg-light rounded-4 p-3">
            <DocumentManager
              :key="selectedCategory"
              :category="selectedCategory"
              :title="getCategoryTitle(selectedCategory)"
              :is-editable="true"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import DocumentManager from '../../components/DocumentManager.vue';

const selectedCategory = ref('document');

const getCategoryTitle = (cat) => {
  const titles = {
    document: 'จัดการข่าวประกาศ / เอกสารทั่วไป',
    policy: 'จัดการนโยบายและระเบียบ',
    pdpa: 'จัดการเอกสาร PDPA',
    sla: 'จัดการเอกสาร SLA',
    handbook: 'จัดการคู่มือการใช้งาน',
    certificate: 'จัดการใบประกาศนียบัตร',
    communication: 'จัดการเอกสารการสื่อสาร'
  };
  return titles[cat] || 'จัดการเอกสาร';
};
</script>

<style scoped>
.page-container {
  background-color: #f8f9fa;
}

.breadcrumb-item a {
  text-decoration: none;
  color: #6c757d;
}
.breadcrumb-item a:hover {
  color: #0d6efd;
}
</style>
