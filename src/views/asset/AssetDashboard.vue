<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold m-0 text-primary">
              <i class="bi bi-pc-display me-2"></i>ทะเบียรครุภัณฑ์คอมพิวเตอร์
            </h5>
            <div class="d-flex gap-2">
              <button
                class="btn btn-outline-secondary rounded-pill px-3"
                @click="$router.push('/home-backoffice')"
              >
                <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
              </button>
              <button
                class="btn btn-info text-white rounded-pill px-3"
                @click="$router.push('/computer-loan/manage')"
              >
                <i class="bi bi-clock-history me-1"></i> ประวัติยืม-คืน
              </button>
              <button class="btn btn-primary rounded-pill px-4" @click="openForm()">
                <i class="bi bi-plus-lg me-2"></i>เพิ่มรายการ
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
              <div class="col-md-2">
                <input
                  type="text"
                  v-model="filters.search"
                  class="form-control rounded-pill"
                  placeholder="ค้นหา (รหัส, ชื่อ...)"
                  @input="fetchAssets"
                />
              </div>
              <div class="col-md-2">
                <select
                  v-model="filters.name"
                  class="form-select rounded-pill"
                  @change="fetchAssets"
                >
                  <option value="">-- ชื่อครุภัณฑ์ --</option>
                  <option v-for="(n, i) in nameList" :key="i" :value="n">
                    {{ n }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <select v-model="filters.os" class="form-select rounded-pill" @change="fetchAssets">
                  <option value="">-- ทุก OS --</option>
                  <option v-for="os in osList" :key="os.id" :value="os.name">
                    {{ os.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <select
                  v-model="filters.year"
                  class="form-select rounded-pill"
                  @change="fetchAssets"
                >
                  <option value="">-- ทุกปี --</option>
                  <option v-for="y in yearList" :key="y" :value="y">25{{ y }}</option>
                </select>
              </div>
              <div class="col-md-2">
                <select
                  v-model="filters.status"
                  class="form-select rounded-pill"
                  @change="fetchAssets"
                >
                  <option value="">-- ทุกสถานะ --</option>
                  <option value="Active">ใช้งานปกติ (Active)</option>
                  <option value="Spare">เครื่องสำรอง (Spare)</option>
                  <option value="Borrowed">ถูกยืม (Borrowed)</option>
                  <option value="Repair">ส่งซ่อม (Repair)</option>
                  <option value="Write-off">ตัดจำหน่าย (Write-off)</option>
                  <option value="Sold">ขาย/โอน (Sold)</option>
                </select>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="bg-light">
                  <tr>
                    <th class="text-center" width="5%">#</th>
                    <th width="10%">รหัสครุภัณฑ์</th>
                    <th width="20%">ชื่อครุภัณฑ์</th>
                    <th width="10%">หมวดหมู่</th>
                    <th width="10%">OS</th>
                    <th width="15%">ผู้รับผิดชอบ / สถานที่</th>
                    <th width="10%" class="text-center">สถานะ</th>
                    <th width="15%">จัดการ</th>
                  </tr>
                </thead>
                <tbody></tbody>
                <tbody>
                  <tr v-for="(asset, index) in paginatedAssets" :key="asset.id">
                    <td class="text-center">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                    <td class="fw-bold text-primary">{{ asset.asset_code }}</td>
                    <td>
                      <div class="fw-bold">{{ asset.name }}</div>
                      <div class="small text-muted">{{ asset.brand }} {{ asset.model }}</div>
                    </td>
                    <td>{{ asset.category_name || asset.type }}</td>
                    <td>
                      <span class="badge bg-light text-dark border">{{ asset.os || '-' }}</span>
                    </td>
                    <td>
                      <div class="fw-bold">{{ asset.responsible_person || '-' }}</div>
                      <div class="small text-muted">
                        <i class="bi bi-geo-alt me-1"></i>{{ asset.location || '-' }}
                      </div>
                    </td>
                    <td class="text-center">
                      <span :class="['badge rounded-pill', getStatusClass(asset.status)]">
                        {{ asset.status }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex gap-2">
                        <button
                          class="btn btn-sm btn-outline-info"
                          @click="viewDetail(asset)"
                          title="ดูรายละเอียด"
                        >
                          <i class="bi bi-eye"></i>
                        </button>
                        <button
                          class="btn btn-sm btn-outline-primary"
                          @click="openForm(asset, true)"
                          title="คัดลอก (Clone)"
                        >
                          <i class="bi bi-files"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" @click="openForm(asset)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button
                          class="btn btn-sm btn-outline-info"
                          @click="printQr(asset)"
                          title="พิมพ์ QR Code"
                        >
                          <i class="bi bi-qr-code"></i>
                        </button>
                        <button
                          class="btn btn-sm btn-outline-danger"
                          @click="deleteAsset(asset)"
                          title="ลบ"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="assets.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูล</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div
              class="d-flex justify-content-between align-items-center mt-3"
              v-if="assets.length > 0"
            >
              <div class="text-muted small">
                แสดง {{ (currentPage - 1) * itemsPerPage + 1 }} ถึง
                {{ Math.min(currentPage * itemsPerPage, assets.length) }} จากทั้งหมด
                {{ assets.length }} รายการ
              </div>
              <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <button class="page-link" @click="changePage(currentPage - 1)">
                      <i class="bi bi-chevron-left"></i>
                    </button>
                  </li>
                  <li
                    class="page-item"
                    v-for="p in totalPages"
                    :key="p"
                    :class="{ active: currentPage === p }"
                  >
                    <button class="page-link" @click="changePage(p)">{{ p }}</button>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <button class="page-link" @click="changePage(currentPage + 1)">
                      <i class="bi bi-chevron-right"></i>
                    </button>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <AssetForm ref="assetForm" @saved="fetchAssets" />
    <AssetDetail ref="assetDetail" />
    <AssetQrPrint ref="assetQrPrint" />
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import AssetForm from './AssetForm.vue';
import AssetDetail from './AssetDetail.vue';
import AssetQrPrint from './AssetQrPrint.vue';

export default {
  name: 'AssetDashboard',
  components: { AssetForm, AssetDetail, AssetQrPrint },
  data() {
    return {
      assets: [],
      osList: [],
      nameList: [],
      yearList: [],
      filters: {
        search: '',
        status: '',
        type: '',
        os: '',
        name: '',
        year: ''
      },
      currentPage: 1,
      itemsPerPage: 20
    };
  },
  mounted() {
    this.fetchAssets();
    this.fetchOSList();
    this.fetchNameList();
    this.fetchYears();
  },
  methods: {
    async fetchYears() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_years.php');
        if (res.data.status === 'success') this.yearList = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchNameList() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_names.php');
        if (res.data.status === 'success') this.nameList = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchOSList() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_os.php');
        if (res.data.status === 'success') this.osList = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchAssets() {
      try {
        const params = new URLSearchParams(this.filters).toString();
        const res = await axios.get(`/api-digital/asset/get_assets.php?${params}`);
        if (res.data.status === 'success') {
          this.assets = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    openForm(asset = null, isClone = false) {
      this.$refs.assetForm.open(asset, isClone);
    },
    viewDetail(asset) {
      this.$refs.assetDetail.open(asset.id);
    },
    printQr(asset) {
      this.$refs.assetQrPrint.open(asset);
    },
    async deleteAsset(asset) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบ ${asset.asset_code} - ${asset.name} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/asset/delete_asset.php', { id: asset.id });
          if (res.data.status === 'success') {
            Swal.fire('ลบสำเร็จ', '', 'success');
            this.fetchAssets();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'Cannot delete asset', 'error');
        }
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
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    }
  },
  computed: {
    totalPages() {
      return Math.ceil(this.assets.length / this.itemsPerPage);
    },
    paginatedAssets() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.assets.slice(start, end);
    }
  }
};
</script>
