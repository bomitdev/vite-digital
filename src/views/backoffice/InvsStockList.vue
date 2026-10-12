<template>
  <div class="page-container min-vh-100 bg-light py-5">
    <div class="container-xxl">
      
      <!-- Header Section -->
      <div class="row align-items-center mb-4 fade-in-up">
        <div class="col-md-6 mb-3 mb-md-0">
          <div class="d-flex align-items-center">
            <button @click="$router.push('/home-backoffice')" class="btn btn-white shadow-sm rounded-circle p-2 me-3 hover-scale">
              <i class="bi bi-arrow-left fs-5"></i>
            </button>
            <div>
              <h3 class="fw-bold text-dark mb-1">คลังวัสดุตั้งต้น (INVS)</h3>
              <p class="text-secondary mb-0 small">ข้อมูลตั้งต้นรายเดือนจากระบบ INVS</p>
            </div>
          </div>
        </div>
        <div class="col-md-6 text-md-end">
          <div class="search-box d-inline-block position-relative shadow-sm rounded-pill w-100" style="max-width: 350px;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input 
              type="text" 
              class="form-control form-control-lg rounded-pill ps-5 border-0 bg-white" 
              placeholder="ค้นหาชื่อวัสดุ หรือ รหัส..." 
              v-model="searchQuery"
              @input="debouncedFetchData"
            >
          </div>
        </div>
      </div>

      <!-- Data Card -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in-up delay-100">
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0 text-primary">
            <i class="bi bi-box-seam-fill me-2"></i>รายการวัสดุทั้งหมด
          </h6>
          <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
            ทั้งหมด {{ totalItems }} รายการ
          </span>
        </div>
        
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="px-4 py-3 text-secondary font-weight-semibold" style="width: 15%">รหัส (Code)</th>
                  <th class="px-4 py-3 text-secondary font-weight-semibold" style="width: 40%">ชื่อวัสดุ / ยา (Name)</th>
                  <th class="px-4 py-3 text-secondary font-weight-semibold" style="width: 15%">ขนาดบรรจุ</th>
                  <th class="px-4 py-3 text-secondary font-weight-semibold text-end" style="width: 15%">ยอดยกมา (จำนวน)</th>
                  <th class="px-4 py-3 text-secondary font-weight-semibold text-end" style="width: 15%">มูลค่ารวม</th>
                </tr>
              </thead>
              <tbody class="border-top-0">
                <tr v-if="loading">
                  <td colspan="5" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted small">กำลังโหลดข้อมูล...</p>
                  </td>
                </tr>
                <tr v-else-if="items.length === 0">
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-black-50"></i>
                    ไม่พบข้อมูลคลังวัสดุ
                  </td>
                </tr>
                <tr v-else v-for="(item, index) in items" :key="index" class="hover-row transition-all">
                  <td class="px-4 py-3">
                    <span class="badge bg-light text-dark border font-monospace">{{ item.working_code }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="fw-bold text-dark">{{ item.drug_name }}</div>
                  </td>
                  <td class="px-4 py-3 text-muted small">
                    {{ item.last_pack_ratio }}
                  </td>
                  <td class="px-4 py-3 text-end">
                    <span class="fw-bold" :class="{'text-danger': item.rm_qty <= 0, 'text-success': item.rm_qty > 0}">
                      {{ Number(item.rm_qty).toLocaleString() }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-end fw-bold text-dark">
                    ฿{{ Number(item.rm_value).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'InvsStockList',
  data() {
    return {
      items: [],
      totalItems: 0,
      loading: true,
      searchQuery: '',
      searchTimeout: null,
      apiUrl: '/backend/api_get_invs_stock.php'
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        const response = await axios.get(this.apiUrl, {
          params: { search: this.searchQuery }
        });
        if (response.data.status === 'success') {
          this.items = response.data.data;
          this.totalItems = response.data.total;
        }
      } catch (error) {
        console.error("Error fetching INVS stock:", error);
      } finally {
        this.loading = false;
      }
    },
    debouncedFetchData() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchData();
      }, 500); // Wait 500ms after user stops typing
    }
  }
};
</script>

<style scoped>
.page-container {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.fade-in-up {
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
  transform: translateY(20px);
}
.delay-100 {
  animation-delay: 0.1s;
}

@keyframes fadeInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hover-scale {
  transition: transform 0.2s ease;
}
.hover-scale:hover {
  transform: scale(1.05);
}

.hover-row:hover {
  background-color: #f1f5fa !important;
}

.font-weight-semibold {
  font-weight: 600;
}
</style>
