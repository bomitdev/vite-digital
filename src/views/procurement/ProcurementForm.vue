<template>
  <div class="modal fade" id="procurementFormModal" tabindex="-1" ref="modal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">{{ form.id ? 'แก้ไขข้อมูลบิล' : 'สร้างรายการบิลใหม่' }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="save">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">เลขที่บิล<span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.bill_number" required placeholder="เช่น INV-2026-001" />
              </div>
              
              <div class="col-12">
                <label class="form-label">ชื่อร้านค้า / บริษัท <span class="text-danger">*</span></label>
                <div class="position-relative">
                  <input
                    type="text"
                    class="form-control"
                    v-model="vendorSearch"
                    @input="onVendorInput"
                    @focus="showVendorDropdown = true"
                    @blur="hideVendorDropdown"
                    required
                    placeholder="พิมพ์เพื่อค้นหาหรือเพิ่มชื่อใหม่"
                    autocomplete="off"
                  />
                  <ul
                    v-if="showVendorDropdown && filteredVendors.length"
                    class="list-group position-absolute w-100 shadow-sm"
                    style="z-index: 9999; top: 100%; max-height: 200px; overflow-y: auto;"
                  >
                    <li
                      v-for="v in filteredVendors"
                      :key="v.source_id"
                      class="list-group-item list-group-item-action py-2 px-3"
                      style="cursor: pointer;"
                      @mousedown.prevent="selectVendor(v.name)"
                    >
                      {{ v.name }}
                    </li>
                  </ul>
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">วันที่บิล <span class="text-danger">*</span></label>
                <input type="date" class="form-control" v-model="form.bill_date" required />
              </div>
              
              <div class="col-md-6">
                <label class="form-label">ยอดเงิน (บาท)</label>
                <input type="number" step="0.01" class="form-control" v-model="form.amount" />
              </div>
              
              <div class="col-12">
                <label class="form-label">หมายเหตุ</label>
                <textarea class="form-control" v-model="form.notes" rows="2" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
              </div>

              <div class="col-12">
                <label class="form-label">แนบคำขออนุมัติซื้อ (รูปภาพ หรือ PDF)</label>
                <input type="file" class="form-control" @change="handleApprovalFileUpload" accept="image/*,application/pdf" ref="approvalFileInput" />
                <div v-if="form.approval_file_path" class="mt-2 small text-muted">
                  มีไฟล์แนบอยู่แล้ว <a :href="getFileUrl(form.approval_file_path)" target="_blank">ดูไฟล์เดิม</a>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">แนบใบสั่งซื้อ (รูปภาพ หรือ PDF)</label>
                <input type="file" class="form-control" @change="handlePoFileUpload" accept="image/*,application/pdf" ref="poFileInput" />
                <div v-if="form.po_file_path" class="mt-2 small text-muted">
                  มีไฟล์แนบอยู่แล้ว <a :href="getFileUrl(form.po_file_path)" target="_blank">ดูไฟล์เดิม</a>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">แนบใบวางบิล (รูปภาพ หรือ PDF)</label>
                <input type="file" class="form-control" @change="handleInvoiceFileUpload" accept="image/*,application/pdf" ref="invoiceFileInput" />
                <div v-if="form.file_path" class="mt-2 small text-muted">
                  มีไฟล์แนบอยู่แล้ว <a :href="getFileUrl(form.file_path)" target="_blank">ดูไฟล์เดิม</a>
                </div>
              </div>
            </div>

            <div class="text-end mt-4">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span> บันทึกข้อมูล
              </button>
            </div>
          </form>
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
  name: 'ProcurementForm',
  data() {
    return {
      bsModal: null,
      submitting: false,
      selectedApprovalFile: null,
      selectedPoFile: null,
      selectedInvoiceFile: null,
      vendors: [],
      vendorSearch: '',
      showVendorDropdown: false,
      form: {
        id: null,
        bill_number: '',
        vendor_name: '',
        amount: 0,
        bill_date: new Date().toISOString().slice(0, 10),
        notes: '',
        file_path: '',
        approval_file_path: '',
        po_file_path: ''
      }
    };
  },
  computed: {
    filteredVendors() {
      if (!this.vendorSearch.trim()) return this.vendors;
      const q = this.vendorSearch.toLowerCase();
      return this.vendors.filter(v => v.name.toLowerCase().includes(q));
    }
  },
  mounted() {
    this.bsModal = new Modal(this.$refs.modal);
    this.fetchVendors();
  },
  methods: {
    async fetchVendors() {
      try {
        const res = await axios.get('/api-digital/procurement/get_vendors.php');
        if (res.data.status === 'success') {
          this.vendors = res.data.data;
        }
      } catch (e) {
        console.error('Cannot load vendors', e);
      }
    },
    onVendorInput() {
      this.form.vendor_name = this.vendorSearch;
      this.showVendorDropdown = true;
    },
    selectVendor(name) {
      this.vendorSearch = name;
      this.form.vendor_name = name;
      this.showVendorDropdown = false;
    },
    hideVendorDropdown() {
      setTimeout(() => { this.showVendorDropdown = false; }, 150);
    },
    open(bill = null) {
      this.resetForm();
      if (bill) {
        this.form = { ...bill };
        this.vendorSearch = bill.vendor_name || '';
      }
      this.bsModal.show();
    },
    resetForm() {
      this.form = {
        id: null,
        bill_number: '',
        vendor_name: '',
        amount: 0,
        bill_date: new Date().toISOString().slice(0, 10),
        notes: '',
        file_path: '',
        approval_file_path: '',
        po_file_path: ''
      };
      this.vendorSearch = '';
      this.selectedApprovalFile = null;
      this.selectedPoFile = null;
      this.selectedInvoiceFile = null;
      if (this.$refs.approvalFileInput) this.$refs.approvalFileInput.value = '';
      if (this.$refs.poFileInput) this.$refs.poFileInput.value = '';
      if (this.$refs.invoiceFileInput) this.$refs.invoiceFileInput.value = '';
    },
    handleApprovalFileUpload(event) {
      this.selectedApprovalFile = event.target.files[0];
    },
    handlePoFileUpload(event) {
      this.selectedPoFile = event.target.files[0];
    },
    handleInvoiceFileUpload(event) {
      this.selectedInvoiceFile = event.target.files[0];
    },
    getFileUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      if (import.meta.env.DEV) {
        return path.startsWith('/') ? path : `/${path}`;
      }
      const baseUrl = window.location.origin;
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    },
    async save() {
      this.submitting = true;
      try {
        const formData = new FormData();
        for (const key in this.form) {
          formData.append(key, this.form[key] !== null ? this.form[key] : '');
        }
        if (this.selectedApprovalFile) {
          formData.append('approval_file', this.selectedApprovalFile);
        }
        if (this.selectedPoFile) {
          formData.append('po_file', this.selectedPoFile);
        }
        if (this.selectedInvoiceFile) {
          formData.append('invoice_file', this.selectedInvoiceFile);
        }

        const res = await axios.post('/api-digital/procurement/save_bill.php', formData);
        
        if (res.data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1500 });
          this.bsModal.hide();
          this.$emit('saved');
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error', err.response?.data?.message || 'Cannot save bill', 'error');
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>
