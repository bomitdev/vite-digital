<template>
  <div class="row g-4 mt-3">
    <div
      class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4"
    >
      <div class="mb-3 mb-md-0">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
              <router-link to="/material-v2" class="text-decoration-none"
                >หน้าหลักวัสดุคอม</router-link
              >
            </li>
            <li class="breadcrumb-item active" aria-current="page">ตั้งค่าการพิมพ์ใบเบิก</li>
          </ol>
        </nav>
        <h4 class="fw-bold mb-0 text-primary">ตั้งค่าการพิมพ์ใบเบิกวัสดุ (Print Settings)</h4>
      </div>
    </div>

    <div v-if="loading" class="col-12 text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">กำลังโหลดข้อมูลการตั้งค่า...</p>
    </div>

    <template v-else>
      <!-- Global Settings -->
      <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 pb-0">
            <h5 class="fw-bold mb-0">
              <i class="bi bi-gear text-primary me-2"></i> ตั้งค่าผู้จ่ายและผู้อนุมัติ (ส่วนกลาง)
            </h5>
          </div>
          <div class="card-body p-4">
            <form @submit.prevent="saveGlobalSettings">
              <div class="mb-3">
                <label class="form-label fw-bold"
                  >ชื่อผู้จ่าย <small class="text-muted">(ผู้จ่ายพัสดุ)</small></label
                >
                <input
                  type="text"
                  class="form-control"
                  v-model="globalSettings.payer_name"
                  list="namesList"
                  placeholder="ระบุชื่อผู้จ่าย"
                />
              </div>
              <div class="mb-4">
                <label class="form-label fw-bold">ตำแหน่งผู้จ่าย</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="globalSettings.payer_position"
                  list="positionsList"
                  placeholder="ระบุตำแหน่งผู้จ่าย"
                />
              </div>

              <hr />

              <div class="mb-3 mt-4">
                <label class="form-label fw-bold"
                  >ชื่อผู้สั่งจ่าย <small class="text-muted">(ผู้อนุมัติใบเบิก)</small></label
                >
                <input
                  type="text"
                  class="form-control"
                  v-model="globalSettings.approver_name"
                  list="namesList"
                  placeholder="ระบุชื่อผู้สั่งจ่าย"
                />
              </div>
              <div class="mb-4">
                <label class="form-label fw-bold">ตำแหน่งผู้สั่งจ่าย</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="globalSettings.approver_position"
                  list="positionsList"
                  placeholder="ระบุตำแหน่งผู้สั่งจ่าย"
                />
              </div>

              <div class="text-end">
                <button type="submit" class="btn btn-primary" :disabled="savingGlobal">
                  <span v-if="savingGlobal" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-save me-2"></i> บันทึกการตั้งค่าส่วนกลาง
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Department Signers Settings -->
      <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div
            class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center"
          >
            <h5 class="fw-bold mb-0">
              <i class="bi bi-diagram-3 text-success me-2"></i> ตั้งค่าผู้เบิกประจำหน่วยงาน
            </h5>
            <button class="btn btn-sm btn-outline-success" @click="openDeptModal(null)">
              <i class="bi bi-plus-lg"></i> เพิ่มหน่วยงาน
            </button>
          </div>
          <div class="card-body p-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>หน่วยงาน</th>
                    <th>ผู้เบิกพัสดุ</th>
                    <th class="text-end">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="departments.length === 0">
                    <td colspan="3" class="text-center text-muted py-3">
                      ยังไม่มีการตั้งค่าหน่วยงาน
                    </td>
                  </tr>
                  <tr v-for="dept in departments" :key="dept.department_name">
                    <td class="fw-bold">{{ dept.department_name }}</td>
                    <td>
                      <div>{{ dept.requester_name || '-' }}</div>
                      <small class="text-muted">{{ dept.requester_position || '-' }}</small>
                    </td>
                    <td class="text-end">
                      <button
                        class="btn btn-sm btn-light text-primary me-1"
                        @click="openDeptModal(dept)"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-light text-danger"
                        @click="deleteDept(dept.department_name)"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal Form for Department Signer -->
    <div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow">
          <div class="modal-header border-bottom-0 pt-4 pb-2 px-4">
            <h5 class="modal-title fw-bold">
              {{ editingDept ? 'แก้ไขข้อมูลหน่วยงาน' : 'เพิ่มการตั้งค่าหน่วยงาน' }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body px-4 pb-4">
            <form @submit.prevent="saveDeptForm">
              <div class="mb-3">
                <label class="form-label fw-bold"
                  >ชื่อหน่วยงาน <span class="text-danger">*</span></label
                >
                <input
                  type="text"
                  class="form-control"
                  v-model="deptForm.department_name"
                  required
                  :disabled="editingDept"
                  list="departmentsList"
                  placeholder="เช่น อุบัติเหตุและฉุกเฉิน"
                />
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold"
                  >ชื่อผู้เบิก <span class="text-danger">*</span></label
                >
                <input
                  type="text"
                  class="form-control"
                  v-model="deptForm.requester_name"
                  required
                  list="namesList"
                  placeholder="เช่น นายแพทย์ใจดี รักษา"
                />
              </div>
              <div class="mb-4">
                <label class="form-label fw-bold">ตำแหน่งผู้เบิก</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="deptForm.requester_position"
                  list="positionsList"
                  placeholder="เช่น นายแพทย์ปฏิบัติการ"
                />
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-success" :disabled="savingDept">
                  <span v-if="savingDept" class="spinner-border spinner-border-sm me-2"></span
                  >บันทึก
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Datalists for Auto-complete -->
    <datalist id="namesList">
      <option v-for="name in apiNames" :key="name" :value="name"></option>
    </datalist>
    <datalist id="positionsList">
      <option v-for="pos in apiPositions" :key="pos" :value="pos"></option>
    </datalist>
    <datalist id="departmentsList">
      <option v-for="dept in apiDepartments" :key="dept" :value="dept"></option>
    </datalist>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtPrintSettings',
  data() {
    return {
      globalSettings: {
        payer_name: '',
        payer_position: '',
        approver_name: '',
        approver_position: ''
      },
      departments: [],
      loading: true,
      savingGlobal: false,
      savingDept: false,

      deptModalInstance: null,
      editingDept: false,
      deptForm: {
        department_name: '',
        requester_name: '',
        requester_position: ''
      },
      apiNames: [],
      apiDepartments: [],
      apiPositions: []
    };
  },
  mounted() {
    this.fetchSettings();
    this.fetchDropdownData();
    this.deptModalInstance = new bootstrap.Modal(document.getElementById('deptModal'));
  },
  methods: {
    async fetchDropdownData() {
      try {
        const res = await axios.get('/api-digital/material_v2/get_requesters_depts.php');
        if (res.data.success) {
          this.apiNames = res.data.requesters || [];
          this.apiDepartments = res.data.departments || [];
          this.apiPositions = res.data.positions || [];
        }
      } catch (err) {
        console.error('Failed to load dropdown data', err);
      }
    },
    async fetchSettings() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/material_v2/get_print_settings.php');
        if (res.data.success) {
          // Merge global settings into our object
          if (res.data.global) {
            this.globalSettings = { ...this.globalSettings, ...res.data.global };
          }
          this.departments = res.data.departments || [];
        }
      } catch (error) {
        console.error('Error fetching settings:', error);
        Swal.fire('Error', 'ไม่สามารถโหลดการตั้งค่าได้', 'error');
      } finally {
        this.loading = false;
      }
    },
    async saveGlobalSettings() {
      this.savingGlobal = true;
      try {
        const res = await axios.post('/api-digital/material_v2/save_global_settings.php', {
          settings: this.globalSettings
        });
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: 'การตั้งค่าส่วนกลางถูกบันทึกแล้ว',
            timer: 1500,
            showConfirmButton: false
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.savingGlobal = false;
      }
    },
    openDeptModal(dept) {
      if (dept) {
        this.editingDept = true;
        this.deptForm = { ...dept };
      } else {
        this.editingDept = false;
        this.deptForm = { department_name: '', requester_name: '', requester_position: '' };
      }
      this.deptModalInstance.show();
    },
    async saveDeptForm() {
      this.savingDept = true;
      try {
        const res = await axios.post(
          '/api-digital/material_v2/save_dept_signer.php',
          this.deptForm
        );
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            timer: 1500,
            showConfirmButton: false
          });
          this.deptModalInstance.hide();
          this.fetchSettings();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.savingDept = false;
      }
    },
    async deleteDept(name) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบการตั้งค่าหน่วยงาน "${name}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบ'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/material_v2/delete_dept_signer.php', {
            department_name: name
          });
          if (res.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'ลบสำเร็จ',
              timer: 1000,
              showConfirmButton: false
            });
            this.fetchSettings();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (error) {
          console.error(error);
          Swal.fire('Error', 'เกิดข้อผิดพลาดในการลบ', 'error');
        }
      }
    }
  }
};
</script>

<style scoped>
.table > :not(caption) > * > * {
  padding: 1rem 0.5rem;
}
</style>
