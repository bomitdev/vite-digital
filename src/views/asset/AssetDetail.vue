<template>
  <div class="modal fade" id="assetDetailModal" tabindex="-1" ref="modal">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow" v-if="asset">
        <div class="modal-header bg-dark text-white">
          <div>
            <h5 class="modal-title fw-bold mb-0">
              <i class="bi bi-pc-display-horizontal me-2"></i>{{ asset.asset_code }} -
              {{ asset.name }}
            </h5>
            <small class="text-secondary-light"
              >{{ asset.brand }} {{ asset.model }} | S/N: {{ asset.serial_number }}</small
            >
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <!-- Tabs -->
          <ul class="nav nav-tabs nav-justified" id="detailTab" role="tablist">
            <li class="nav-item">
              <button
                class="nav-link active rounded-0 py-3"
                id="overview-tab"
                data-bs-toggle="tab"
                data-bs-target="#overview"
                type="button"
              >
                <i class="bi bi-info-circle me-1"></i>ข้อมูลทั่วไป
              </button>
            </li>
            <li class="nav-item">
              <button
                class="nav-link rounded-0 py-3"
                id="software-tab"
                data-bs-toggle="tab"
                data-bs-target="#software"
                type="button"
              >
                <i class="bi bi-microsoft me-1"></i>ซอฟต์แวร์ ({{ softwareList.length }})
              </button>
            </li>
            <li class="nav-item">
              <button
                class="nav-link rounded-0 py-3"
                id="maintenance-tab"
                data-bs-toggle="tab"
                data-bs-target="#maintenance"
                type="button"
              >
                <i class="bi bi-tools me-1"></i>ประวัติซ่อมบำรุง ({{ maintenanceList.length }})
              </button>
            </li>
          </ul>

          <div class="tab-content p-4" id="detailTabContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview">
              <div class="row g-4">
                <div class="col-md-4 text-center">
                  <div
                    class="bg-light border rounded p-5 d-flex align-items-center justify-content-center"
                    style="height: 250px"
                  >
                    <img
                      v-if="asset.image_path"
                      :src="getImageUrl(asset.image_path)"
                      class="img-fluid"
                      style="max-height: 100%"
                    />
                    <i v-else class="bi bi-pc-display fs-1 text-secondary"></i>
                  </div>
                </div>
                <div class="col-md-8">
                  <table class="table table-borderless">
                    <tbody>
                      <tr>
                        <th width="30%">สถานะ:</th>
                        <td>
                          <span :class="['badge', getStatusClass(asset.status)]">{{
                            asset.status
                          }}</span>
                        </td>
                      </tr>
                      <tr>
                        <th>ผู้รับผิดชอบ:</th>
                        <td>{{ asset.responsible_person }}</td>
                      </tr>
                      <tr>
                        <th>สถานที่:</th>
                        <td>{{ asset.location }}</td>
                      </tr>
                      <tr>
                        <th>วันที่จัดซื้อ:</th>
                        <td>{{ formatDate(asset.purchase_date) }}</td>
                      </tr>
                      <tr>
                        <th>หมดประกัน:</th>
                        <td>{{ formatDate(asset.warranty_expire_date) }}</td>
                      </tr>
                      <tr>
                        <th>ราคา:</th>
                        <td>{{ formatCurrency(asset.price) }}</td>
                      </tr>
                      <tr>
                        <th colspan="2" class="border-bottom pb-2 mt-2">สเปคเครื่อง</th>
                      </tr>
                      <tr>
                        <th>CPU:</th>
                        <td>{{ asset.spec_cpu }}</td>
                      </tr>
                      <tr>
                        <th>RAM:</th>
                        <td>{{ asset.spec_ram }}</td>
                      </tr>
                      <tr>
                        <th>Storage:</th>
                        <td>{{ asset.spec_storage }}</td>
                      </tr>
                      <tr>
                        <th>OS:</th>
                        <td>{{ asset.os }}</td>
                      </tr>
                      <tr>
                        <th>หมายเหตุ:</th>
                        <td>{{ asset.notes }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Software Tab -->
            <div class="tab-pane fade" id="software">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0 text-primary">รายการซอฟต์แวร์ที่ติดตั้ง</h6>
                <button
                  class="btn btn-sm btn-outline-primary"
                  @click="showSoftwareForm = !showSoftwareForm"
                >
                  <i class="bi bi-plus-lg"></i> เพิ่มซอฟต์แวร์
                </button>
              </div>

              <!-- Add Software Form -->
              <div v-if="showSoftwareForm" class="card bg-light mb-3 border-0">
                <div class="card-body">
                  <form @submit.prevent="saveSoftware" class="row g-2">
                    <div class="col-md-4">
                      <input
                        type="text"
                        v-model="swForm.software_name"
                        list="software-options"
                        @change="onSoftwareSelect"
                        class="form-control form-control-sm"
                        placeholder="ค้นหาจากทะเบียนซอฟต์แวร์ *"
                        required
                      />
                      <datalist id="software-options">
                        <option
                          v-for="sw in availableSoftwareList"
                          :key="sw.id"
                          :value="sw.software_name"
                        ></option>
                      </datalist>
                    </div>
                    <div class="col-md-2">
                      <input
                        type="text"
                        v-model="swForm.version"
                        class="form-control form-control-sm"
                        placeholder="เวอร์ชัน"
                      />
                    </div>
                    <div class="col-md-3">
                      <input
                        type="text"
                        v-model="swForm.license_key"
                        class="form-control form-control-sm"
                        placeholder="License Key"
                      />
                    </div>
                    <div class="col-md-3">
                      <select v-model="swForm.license_type" class="form-select form-select-sm">
                        <option value="Perpetual">ถาวร (Perpetual)</option>
                        <option value="Subscription">รายปี (Subscription)</option>
                        <option value="Trial">ทดลองใช้ (Trial)</option>
                        <option value="Free">ฟรี (Free)</option>
                        <option value="Volume">Volume License</option>
                      </select>
                    </div>
                    <div class="col-12 text-end">
                      <button
                        type="button"
                        class="btn btn-sm btn-secondary me-2"
                        @click="showSoftwareForm = false"
                      >
                        ยกเลิก
                      </button>
                      <button type="submit" class="btn btn-sm btn-primary">บันทึก</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-bordered table-sm">
                  <thead class="table-light">
                    <tr>
                      <th>ชื่อซอฟต์แวร์</th>
                      <th>Version</th>
                      <th>License Key</th>
                      <th>Type</th>
                      <th width="10%">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sw in softwareList" :key="sw.id">
                      <td>{{ sw.software_name }}</td>
                      <td>{{ sw.version }}</td>
                      <td class="text-monospace small">{{ sw.license_key }}</td>
                      <td>
                        <span class="badge bg-secondary">{{ sw.license_type }}</span>
                      </td>
                      <td class="text-center">
                        <div
                          v-if="!String(sw.id).startsWith('sw_reg')"
                          class="d-flex align-items-center justify-content-center gap-2"
                        >
                          <span
                            v-if="sw.is_synced"
                            class="badge bg-light text-success border border-success"
                            title="ซิงก์กับระบบทะเบียนซอฟต์แวร์กลางแล้ว"
                            style="font-size: 0.75rem"
                          >
                            <i class="bi bi-link-45deg"></i> ซิงก์แล้ว
                          </span>
                          <button
                            class="btn btn-sm btn-link text-danger p-0"
                            @click="deleteSoftware(sw.id)"
                            title="ลบซอฟต์แวร์ที่ติดตั้งแบบ Manual"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                        <span
                          v-else
                          class="badge bg-light text-muted border"
                          title="จัดการผ่านระบบทะเบียนซอฟต์แวร์กลาง"
                        >
                          <i class="bi bi-link-45deg"></i> ซิงก์แล้ว
                        </span>
                      </td>
                    </tr>
                    <tr v-if="softwareList.length === 0">
                      <td colspan="5" class="text-center text-muted py-3">ไม่มีข้อมูลซอฟต์แวร์</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Maintenance Tab -->
            <div class="tab-pane fade" id="maintenance">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0 text-primary">ประวัติการซ่อมบำรุง</h6>
                <button class="btn btn-sm btn-outline-danger" @click="goToRepair">
                  <i class="bi bi-wrench-adjustable-circle"></i> แจ้งซ่อม
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover table-sm">
                  <thead class="table-light">
                    <tr>
                      <th width="15%">วันที่</th>
                      <th>อาการ/ปัญหา</th>
                      <th>การแก้ไข</th>
                      <th width="10%">ค่าใช้จ่าย</th>
                      <th width="15%">ผู้ดำเนินการ</th>
                      <th width="10%">สถานะ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="log in maintenanceList" :key="log.id">
                      <td>{{ formatDate(log.repair_date) }}</td>
                      <td>{{ log.issue }}</td>
                      <td>{{ log.solution || '-' }}</td>
                      <td class="text-end">{{ formatCurrency(log.cost) }}</td>
                      <td>{{ log.technician || '-' }}</td>
                      <td>
                        <span :class="['badge rounded-pill', getMaintStatusClass(log.status)]">{{
                          log.status
                        }}</span>
                      </td>
                    </tr>
                    <tr v-if="maintenanceList.length === 0">
                      <td colspan="6" class="text-center text-muted py-3">
                        ยังไม่มีประวัติการซ่อม
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

export default {
  name: 'AssetDetail',
  data() {
    return {
      bsModal: null,
      asset: null,
      softwareList: [],
      maintenanceList: [],
      availableSoftwareList: [],
      showSoftwareForm: false,
      swForm: {
        software_name: '',
        version: '',
        license_key: '',
        license_type: 'Perpetual'
      }
    };
  },
  mounted() {
    this.bsModal = new Modal(this.$refs.modal);
    this.fetchAvailableSoftware();
  },
  methods: {
    async fetchAvailableSoftware() {
      try {
        const res = await axios.get('/api-digital/software/get_software.php');
        if (res.data.success) {
          this.availableSoftwareList = res.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch available software', err);
      }
    },
    onSoftwareSelect() {
      const selected = this.availableSoftwareList.find(
        (sw) => sw.software_name === this.swForm.software_name
      );
      if (selected) {
        this.swForm.version = selected.version || '';
        this.swForm.license_key = selected.license_key || '';
        this.swForm.license_type = selected.license_type || 'Perpetual';
      }
    },
    async open(id) {
      if (!id) return;

      try {
        const res = await axios.get(`/api-digital/asset/get_asset_detail.php?id=${id}`);
        if (res.data.status === 'success') {
          this.asset = res.data.data.asset;
          this.softwareList = res.data.data.software;
          this.maintenanceList = res.data.data.maintenance;
          this.bsModal.show();
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to load details', 'error');
      }
    },
    async saveSoftware() {
      try {
        const payload = { ...this.swForm, asset_id: this.asset.id };
        const res = await axios.post('/api-digital/asset/save_software.php', payload);
        if (res.data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'Saved', showConfirmButton: false, timer: 1000 });
          this.showSoftwareForm = false;
          // Reload
          this.open(this.asset.id);
          this.swForm = {
            software_name: '',
            version: '',
            license_key: '',
            license_type: 'Perpetual'
          };
        }
      } catch (err) {
        Swal.fire('Error', 'Save failed', 'error');
      }
    },
    async deleteSoftware(id) {
      if (!confirm('Delete this software?')) return;
      try {
        const res = await axios.post('/api-digital/asset/delete_software.php', { id });
        if (res.data.status === 'success') this.open(this.asset.id);
      } catch (err) {
        Swal.fire('Error', 'Delete failed', 'error');
      }
    },
    goToRepair() {
      this.bsModal.hide();
      this.$router.push({
        name: 'ComputerRepair',
        query: { asset_code: this.asset.asset_code }
      });
    },
    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('th-TH');
    },
    formatCurrency(val) {
      if (!val) return '-';
      return parseFloat(val).toLocaleString('th-TH', { style: 'currency', currency: 'THB' });
    },
    getStatusClass(status) {
      switch (status) {
        case 'Active':
          return 'bg-success';
        case 'Spare':
          return 'bg-primary';
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
    getMaintStatusClass(status) {
      switch (status) {
        case 'Completed':
          return 'bg-success';
        case 'In Progress':
          return 'bg-info text-dark';
        case 'Pending':
          return 'bg-warning text-dark';
        case 'Cannot Fix':
          return 'bg-danger';
        default:
          return 'bg-secondary';
      }
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      return path.startsWith('/') ? path : `/${path}`;
    }
  }
};
</script>
