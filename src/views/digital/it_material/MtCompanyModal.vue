<template>
  <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true" ref="modalElement">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow rounded-4">
        <!-- Modal Header -->
        <div class="modal-header bg-primary bg-opacity-10 border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-dark">
            <i class="bi bi-building-fill text-primary me-2"></i>จัดการแหล่งที่มา / ผู้จำหน่าย (วัสดุคอมพิวเตอร์)
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-4">
          <!-- Add / Edit Form -->
          <div
            class="card border-0 rounded-3 p-3 mb-4 transition-all"
            :class="editingCompany ? 'bg-primary-subtle border border-primary-subtle' : 'bg-light'"
          >
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6
                class="fw-bold mb-0"
                :class="editingCompany ? 'text-primary' : 'text-secondary'"
              >
                <i class="bi" :class="editingCompany ? 'bi-pencil-square me-1' : 'bi-plus-circle me-1'"></i>
                {{ editingCompany ? `แก้ไขผู้จำหน่าย: "${editingCompany.company_name}"` : 'เพิ่มแหล่งที่มา / ผู้จำหน่ายใหม่' }}
              </h6>
              <button
                v-if="editingCompany"
                type="button"
                class="btn btn-sm btn-link text-muted p-0 text-decoration-none"
                @click="cancelEdit"
              >
                <i class="bi bi-x-circle me-1"></i>ยกเลิกการแก้ไข
              </button>
            </div>

            <form @submit.prevent="saveCompany" class="row g-2 align-items-end">
              <div class="col-md-9">
                <label class="form-label small fw-bold">ชื่อแหล่งที่มา / ผู้จำหน่าย <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control form-control-sm"
                  v-model="formName"
                  placeholder="เช่น บริษัท แอดไวซ์ ไอที อินฟินิท จำกัด, สินค้าบริจาค, ร้านไอทีพาณิชย์"
                  required
                />
              </div>
              <div class="col-md-3 d-flex gap-1">
                <button
                  type="submit"
                  class="btn btn-sm w-100 fw-bold"
                  :class="editingCompany ? 'btn-primary' : 'btn-success'"
                  :disabled="isSaving"
                >
                  <i class="bi" :class="editingCompany ? 'bi-check-lg me-1' : 'bi-plus-lg me-1'"></i>
                  {{ editingCompany ? 'บันทึกแก้ไข' : 'เพิ่มผู้จำหน่าย' }}
                </button>
                <button
                  v-if="editingCompany"
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="cancelEdit"
                  title="ยกเลิก"
                >
                  ยกเลิก
                </button>
              </div>
            </form>
            <div class="form-text small mt-1 text-muted">
              * รายชื่อผู้จำหน่ายจะแสดงใน Dropdown แนะนำตอนบันทึกรับเข้าวัสดุคอมพิวเตอร์
            </div>
          </div>

          <!-- Search & Header Bar -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-muted">
              <i class="bi bi-list-ul me-1"></i>รายชื่อทั้งหมด ({{ filteredCompanies.length }} รายการ)
            </span>
            <div class="w-50">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input
                  type="text"
                  class="form-control border-start-0"
                  v-model="search"
                  placeholder="ค้นหาชื่อผู้จำหน่าย..."
                />
                <button
                  v-if="search"
                  class="btn btn-outline-secondary"
                  type="button"
                  @click="search = ''"
                >
                  <i class="bi bi-x"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Table of Companies -->
          <div class="table-responsive border rounded-3" style="max-height: 360px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width: 50px;" class="ps-3">#</th>
                  <th>ชื่อแหล่งที่มา / ผู้จำหน่าย</th>
                  <th class="text-center" style="width: 120px;">Lot ในคลัง</th>
                  <th class="text-center" style="width: 120px;">ประวัติรับเข้า</th>
                  <th class="text-end pe-3" style="width: 110px;">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="5" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div> กำลังโหลดข้อมูล...
                  </td>
                </tr>
                <tr v-else-if="filteredCompanies.length === 0">
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block text-secondary opacity-50 mb-1"></i>
                    ไม่พบข้อมูลผู้จำหน่าย
                  </td>
                </tr>
                <tr
                  v-for="(comp, index) in filteredCompanies"
                  :key="comp.id"
                  :class="editingCompany && editingCompany.id === comp.id ? 'table-primary' : ''"
                >
                  <td class="ps-3 text-muted small">{{ index + 1 }}</td>
                  <td>
                    <span class="fw-semibold text-dark">{{ comp.company_name }}</span>
                  </td>
                  <td class="text-center">
                    <span
                      class="badge rounded-pill"
                      :class="Number(comp.active_lots_count) > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border'"
                    >
                      <i class="bi bi-box-seam me-1"></i>{{ comp.active_lots_count || 0 }} Lot
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge rounded-pill bg-light text-secondary border">
                      <i class="bi bi-receipt me-1"></i>{{ comp.transactions_count || 0 }} ครั้ง
                    </span>
                  </td>
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <button
                        type="button"
                        class="btn btn-outline-primary"
                        @click="startEdit(comp)"
                        title="แก้ไขชื่อผู้จำหน่าย"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-danger"
                        @click="deleteCompany(comp)"
                        title="ลบ"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
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
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtCompanyModal',
  props: {
    modalId: {
      type: String,
      default: 'companyManageModal'
    }
  },
  data() {
    return {
      companies: [],
      loading: false,
      isSaving: false,
      search: '',
      formName: '',
      editingCompany: null,
      modalInstance: null
    };
  },
  computed: {
    filteredCompanies() {
      if (!this.search) return this.companies;
      const q = this.search.toLowerCase();
      return this.companies.filter(c => c.company_name.toLowerCase().includes(q));
    }
  },
  methods: {
    async fetchCompanies() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/it_material/it_companies.php');
        if (res.data.status === 'success') {
          this.companies = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching companies:', err);
      } finally {
        this.loading = false;
      }
    },
    openModal() {
      if (!this.modalInstance) {
        this.modalInstance = new bootstrap.Modal(this.$refs.modalElement);
      }
      this.fetchCompanies();
      this.cancelEdit();
      this.modalInstance.show();
    },
    startEdit(comp) {
      this.editingCompany = { ...comp };
      this.formName = comp.company_name;
    },
    cancelEdit() {
      this.editingCompany = null;
      this.formName = '';
    },
    async saveCompany() {
      const name = this.formName.trim();
      if (!name) return;

      this.isSaving = true;
      try {
        const payload = {
          company_name: name
        };
        if (this.editingCompany && this.editingCompany.id) {
          payload.id = this.editingCompany.id;
        }

        const res = await axios.post('/api-digital/it_material/it_save_company.php', payload);
        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          this.cancelEdit();
          await this.fetchCompanies();
          this.$emit('updated');
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
      } finally {
        this.isSaving = false;
      }
    },
    async deleteCompany(comp) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        html: `ต้องการลบผู้จำหน่าย <b>"${comp.company_name}"</b> หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/it_material/it_delete_company.php', {
          id: comp.id
        });
        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'ลบสำเร็จ',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          if (this.editingCompany && this.editingCompany.id === comp.id) {
            this.cancelEdit();
          }
          await this.fetchCompanies();
          this.$emit('updated');
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
      }
    }
  }
};
</script>

<style scoped>
.transition-all {
  transition: all 0.2s ease-in-out;
}
</style>
