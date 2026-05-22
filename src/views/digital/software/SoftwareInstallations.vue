<template>
  <div
    class="modal fade"
    id="installationsModal"
    tabindex="-1"
    aria-labelledby="installationsModalLabel"
    aria-hidden="true"
    ref="modalRef"
  >
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-light border-bottom-0 pt-4 px-4 pb-3">
          <h5 class="modal-title fw-bold text-dark" id="installationsModalLabel">
            <i class="bi bi-pc-display-horizontal me-2 text-primary"></i>
            การติดตั้ง: {{ software?.software_name || 'Software' }}
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body px-4 py-4">
          <!-- Summary Alert -->
          <div class="alert alert-info d-flex align-items-center mb-4 border-0 fs-6">
            <i class="bi bi-info-circle-fill me-3 fs-4"></i>
            <div>
              <strong>การใช้งาน:</strong> {{ installations.length }} /
              {{ software?.max_installations === null ? 'ไม่จำกัด' : software?.max_installations }}
              สิทธิ์ที่ใช้ไป
            </div>
          </div>

          <!-- Add New Installation Form -->
          <div class="card bg-white border border-light-subtle shadow-sm mb-4">
            <div class="card-body p-3">
              <h6 class="fw-bold mb-3">บันทึกการติดตั้งใหม่</h6>
              <form @submit.prevent="addInstallation" class="row g-2 align-items-end">
                <div class="col-md-3">
                  <label class="form-label form-label-sm"
                    >ชื่อเครื่อง <span class="text-danger">*</span></label
                  >
                  <input
                    type="text"
                    v-model="form.machine_name"
                    class="form-control form-control-sm"
                    required
                    list="assetListOptions"
                    placeholder="ค้นหารหัสครุภัณฑ์หรือชื่อ..."
                    @change="onMachineSelected"
                  />
                  <datalist id="assetListOptions">
                    <option
                      v-for="asset in assetList"
                      :key="asset.id"
                      :value="asset.asset_code + ' - ' + asset.name"
                    ></option>
                  </datalist>
                </div>
                <div class="col-md-3">
                  <label class="form-label form-label-sm">ผู้ใช้งาน</label>
                  <input
                    type="text"
                    v-model="form.user_name"
                    class="form-control form-control-sm"
                    placeholder="John Doe"
                    title="จะเติมให้อัตโนมัติหากเลือกครุภัณฑ์"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label form-label-sm">วันที่ติดตั้ง</label>
                  <input
                    type="date"
                    v-model="form.install_date"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-3">
                  <button
                    type="submit"
                    class="btn btn-sm btn-primary w-100 shadow-sm"
                    :disabled="isLimitReached"
                  >
                    <i class="bi bi-plus-circle me-1"></i> เพิ่ม
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Installations Table -->
          <div class="table-responsive border border-light-subtle rounded-3">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="table-light border-bottom">
                <tr>
                  <th scope="col" class="py-3 ps-3">เครื่อง</th>
                  <th scope="col" class="py-3">ผู้ใช้งาน</th>
                  <th scope="col" class="py-3">วันที่ติดตั้ง</th>
                  <th scope="col" class="py-3 text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="installations.length === 0">
                  <td colspan="4" class="text-center text-muted py-4">ยังไม่มีประวัติการติดตั้ง</td>
                </tr>
                <tr v-for="item in installations" :key="item.id" class="border-bottom">
                  <td class="ps-3 fw-medium text-dark">{{ item.machine_name }}</td>
                  <td>{{ item.user_name || '-' }}</td>
                  <td>
                    {{
                      item.install_date
                        ? new Date(item.install_date).toLocaleDateString('th-TH')
                        : '-'
                    }}
                  </td>
                  <td class="text-center">
                    <button
                      class="btn btn-sm btn-outline-danger shadow-sm border-0"
                      @click="deleteInstallation(item.id)"
                      title="ลบ"
                    >
                      <i class="bi bi-trash3-fill"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer bg-light border-top-0 px-4 py-3">
          <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">
            ปิด
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'SoftwareInstallations',
  props: {
    software: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      installations: [],
      assetList: [],
      form: {
        machine_name: '',
        user_name: '',
        install_date: new Date().toISOString().split('T')[0]
      }
    };
  },
  computed: {
    isLimitReached() {
      if (!this.software || this.software.max_installations === null) return false;
      return this.installations.length >= this.software.max_installations;
    }
  },
  watch: {
    software: {
      handler(newVal) {
        if (newVal && newVal.id) {
          this.fetchInstallations();
          this.resetForm();
        }
      },
      immediate: true
    }
  },
  methods: {
    resetForm() {
      this.form = {
        machine_name: '',
        user_name: '',
        install_date: new Date().toISOString().split('T')[0] // default today
      };
    },
    async fetchAssets() {
      try {
        const res = await axios.get('/api-digital/asset/get_assets.php');
        if (res.data.status === 'success') {
          this.assetList = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching assets', error);
      }
    },
    onMachineSelected() {
      // Find asset that matches the entered machine_name string
      const selected = this.assetList.find(
        (a) => `${a.asset_code} - ${a.name}` === this.form.machine_name
      );
      if (selected && selected.responsible_person) {
        this.form.user_name = selected.responsible_person;
      }
    },
    async fetchInstallations() {
      if (!this.software?.id) return;
      try {
        const res = await axios.get(
          `/api-digital/software/get_installations.php?software_id=${this.software.id}`
        );
        if (res.data.success) {
          this.installations = res.data.data;
          this.$emit('installations-updated', this.installations.length);
        }
      } catch (error) {
        console.error('Error fetching installations', error);
      }
    },
    async addInstallation() {
      if (this.isLimitReached) {
        Swal.fire('ถึงขีดจำกัดแล้ว', 'ไม่สามารถติดตั้งเกินจำนวนที่กำหนดได้', 'warning');
        return;
      }
      try {
        const payload = {
          software_id: this.software.id,
          ...this.form
        };
        const res = await axios.post('/api-digital/software/add_installation.php', payload);
        if (res.data.success) {
          this.fetchInstallations();
          this.resetForm();
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'บันทึกการติดตั้งเรียบร้อย',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (error) {
        console.error('Error adding installation', error);
        Swal.fire('Error', 'Server error.', 'error');
      }
    },
    async deleteInstallation(id) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบการติดตั้ง?',
        text: 'ข้อมูลการติดตั้งนี้จะถูกลบอย่างถาวร',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#secondary',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/software/delete_installation.php', { id });
          if (res.data.success) {
            this.fetchInstallations();
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: 'ลบข้อมูลการติดตั้งเรียบร้อย',
              showConfirmButton: false,
              timer: 1500
            });
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (error) {
          console.error(error);
        }
      }
    }
  },
  mounted() {
    this.fetchAssets();
  }
};
</script>

<style scoped>
.form-label-sm {
  font-size: 0.825rem;
  font-weight: 600;
  color: #555;
  margin-bottom: 0.25rem;
}
</style>
