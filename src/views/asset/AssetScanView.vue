<template>
  <div class="min-vh-100 d-flex flex-column bg-light pb-5">
    <!-- Header -->
    <header
      class="bg-primary text-white py-4 shadow-sm"
      style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px"
    >
      <div class="container d-flex align-items-center justify-content-center flex-column">
        <div class="bg-white rounded-circle p-2 shadow-sm mb-2">
          <i class="bi bi-pc-display text-primary" style="font-size: 2rem; line-height: 1"></i>
        </div>
        <h5 class="fw-bold mb-0">ข้อมูลครุภัณฑ์</h5>
        <small class="opacity-75">โรงพยาบาลชานุมาน</small>
      </div>
    </header>

    <div class="container mt-4 px-3 mb-5" style="max-width: 600px">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-5">
        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem"></i>
        <h5 class="mt-3 fw-bold text-dark">ไม่พบข้อมูล</h5>
        <p class="text-muted">{{ errorMsg }}</p>
      </div>

      <!-- Detail Card -->
      <div v-else-if="asset" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <!-- Status Ribbon -->
        <div
          class="text-center text-white py-2 fw-bold"
          :class="getStatusClass(asset.status)"
          style="font-size: 0.9rem"
        >
          <i :class="getStatusIcon(asset.status)" class="me-1"></i>
          สถานะ: {{ asset.status }}
        </div>

        <div class="card-body p-4">
          <div v-if="asset.image_path" class="text-center mb-4">
            <img
              :src="getImageUrl(asset.image_path)"
              class="img-fluid rounded-3 shadow-sm border"
              style="max-height: 220px; object-fit: cover"
              alt="Asset Image"
            />
          </div>
          <div class="text-center mb-4">
            <h4 class="fw-bold text-primary mb-1">{{ asset.asset_code }}</h4>

            <h6 class="text-dark fw-bold mb-0">{{ asset.name }}</h6>
            <div class="text-muted small mt-1">{{ asset.brand }} {{ asset.model }}</div>
          </div>

          <div class="list-group list-group-flush border-top pt-2">
            <div
              class="list-group-item px-0 py-3 border-bottom-0 border-light d-flex align-items-start"
            >
              <div class="bg-light rounded p-2 me-3 text-center" style="width: 40px">
                <i class="bi bi-geo-alt-fill text-danger fs-5"></i>
              </div>
              <div class="flex-grow-1">
                <div class="text-muted small mb-1">สถานที่ติดตั้ง</div>
                <div class="fw-bold text-dark">{{ asset.location || 'ไม่ระบุ' }}</div>
              </div>
            </div>

            <div
              class="list-group-item px-0 py-3 border-bottom-0 border-light d-flex align-items-start"
            >
              <div class="bg-light rounded p-2 me-3 text-center" style="width: 40px">
                <i class="bi bi-person-badge-fill text-info fs-5"></i>
              </div>
              <div class="flex-grow-1">
                <div class="text-muted small mb-1">ผู้รับผิดชอบ</div>
                <div class="fw-bold text-dark">{{ asset.responsible_person || 'ไม่ระบุ' }}</div>
              </div>
            </div>

            <!-- Specs Accordion -->
            <div class="list-group-item px-0 py-3 border-bottom-0 border-light mt-2">
              <div class="accordion accordion-flush" id="specsAccordion">
                <div class="accordion-item border rounded-3 overflow-hidden shadow-sm">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button collapsed py-3 fw-bold bg-light"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#collapseSpecs"
                      aria-expanded="false"
                      aria-controls="collapseSpecs"
                    >
                      <i class="bi bi-memory me-2 text-primary"></i> สเปคเครื่อง & ระบบปฏิบัติการ
                    </button>
                  </h2>
                  <div
                    id="collapseSpecs"
                    class="accordion-collapse collapse"
                    data-bs-parent="#specsAccordion"
                  >
                    <div class="accordion-body bg-white pt-2 pb-3 px-3">
                      <div class="row g-2">
                        <div class="col-12 border-bottom pb-2 mb-2">
                          <span class="text-muted small d-block">OS</span>
                          <span class="fw-semibold text-dark"
                            ><i class="bi bi-windows me-1 text-info"></i>{{ asset.os || '-' }}</span
                          >
                        </div>
                        <div class="col-12 border-bottom pb-2 mb-2">
                          <span class="text-muted small d-block">CPU</span>
                          <span class="fw-semibold text-dark">{{ asset.spec_cpu || '-' }}</span>
                        </div>
                        <div class="col-6">
                          <span class="text-muted small d-block">RAM</span>
                          <span class="fw-semibold text-dark">{{ asset.spec_ram || '-' }}</span>
                        </div>
                        <div class="col-6">
                          <span class="text-muted small d-block">Storage</span>
                          <span class="fw-semibold text-dark">{{ asset.spec_storage || '-' }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Specs Accordion -->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AssetScanView',
  data() {
    return {
      assetCode: '',
      asset: null,
      loading: true,
      error: false,
      errorMsg: ''
    };
  },
  mounted() {
    // Get code from route params
    this.assetCode = this.$route.params.code;
    if (this.assetCode) {
      this.fetchAssetDetails();
    } else {
      this.error = true;
      this.loading = false;
      this.errorMsg = 'รหัสครุภัณฑ์ไม่ถูกต้อง';
    }
  },
  methods: {
    async fetchAssetDetails() {
      try {
        // Find asset by code by using the general get_assets.php API with search filter
        // since we only know the code, not the DB ID.
        this.loading = true;

        const params = new URLSearchParams({ code: this.assetCode }).toString();
        const res = await axios.get(`/api-digital/asset/get_asset_public.php?${params}`);

        if (res.data.status === 'success' && res.data.data) {
          this.asset = res.data.data;
          this.error = false;
        } else {
          this.error = true;
          this.errorMsg = 'ไม่พบข้อมูลครุภัณฑ์รหัสนี้ในระบบ';
        }
      } catch (err) {
        console.error(err);
        this.error = true;
        this.errorMsg = 'เกิดข้อผิดพลาดในการดึงข้อมูล';
      } finally {
        this.loading = false;
      }
    },
    getStatusClass(status) {
      switch (status) {
        case 'Active':
          return 'bg-success';
        case 'Spare':
          return 'bg-primary';
        case 'Borrowed':
          return 'bg-info text-dark';
        case 'Repair':
          return 'bg-warning text-dark';
        case 'Write-off':
          return 'bg-dark';
        case 'Sold':
          return 'bg-secondary';
        default:
          return 'bg-secondary';
      }
    },
    getStatusIcon(status) {
      switch (status) {
        case 'Active':
          return 'bi-check-circle-fill';
        case 'Spare':
          return 'bi-box-seam-fill';
        case 'Repair':
          return 'bi-tools';
        default:
          return 'bi-info-circle-fill';
      }
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const baseUrl = import.meta.env.VITE_BACKEND_URL || '';
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    }
  }
};
</script>

<style scoped>
body {
  background-color: #f8f9fa;
}
.accordion-button:not(.collapsed) {
  color: #0d6efd;
  background-color: #e7f1ff;
  box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.125);
}
.accordion-button:focus {
  box-shadow: none;
  border-color: rgba(0, 0, 0, 0.125);
}
</style>
