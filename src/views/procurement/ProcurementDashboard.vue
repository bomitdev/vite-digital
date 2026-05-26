<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold m-0 text-primary">
              <i class="bi bi-cart-check me-2"></i>ระบบจัดการการจัดซื้อคอมพิวเตอร์และอุปกรณ์
            </h5>
            <div class="d-flex gap-2">
              <button
                class="btn btn-outline-secondary rounded-pill px-3"
                @click="$router.push('/home-backoffice')"
              >
                <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
              </button>
              <button class="btn btn-primary rounded-pill px-4" @click="openForm()">
                <i class="bi bi-plus-lg me-2"></i>สร้างรายการ
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
              <div class="col-md-3">
                <input
                  type="text"
                  v-model="filters.search"
                  class="form-control rounded-pill"
                  placeholder="ค้นหา (เลขที่บิล, ชื่อร้านค้า, หมายเหตุ...)"
                  @input="fetchBills"
                />
              </div>
              <div class="col-md-3">
                <select
                  v-model="filters.status"
                  class="form-select rounded-pill"
                  @change="fetchBills"
                >
                  <option value="">-- ทุกสถานะ --</option>
                  <option value="Draft">รอดำเนินการ (Draft)</option>
                  <option value="Forwarded">ส่งให้บริหารแล้ว (Forwarded)</option>
                  <option value="Received">บริหารรับเอกสารแล้ว (Received)</option>
                </select>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="bg-light">
                  <tr>
                    <th class="text-center" width="5%">#</th>
                    <th width="15%">เลขที่บิล</th>
                    <th width="20%">ชื่อร้านค้า/บริษัท</th>
                    <th width="10%">ยอดเงิน</th>
                    <th width="10%">วันที่บิล</th>
                    <th width="15%">สถานะ</th>
                    <th width="15%">ข้อมูลการส่งมอบ</th>
                    <th width="10%" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(bill, index) in bills" :key="bill.id">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td class="fw-bold text-primary">{{ bill.bill_number }}</td>
                    <td>{{ bill.vendor_name }}</td>
                    <td>{{ formatCurrency(bill.amount) }}</td>
                    <td>{{ formatDate(bill.bill_date) }}</td>
                    <td>
                      <span :class="['badge rounded-pill', getStatusClass(bill.status)]">
                        {{ getStatusText(bill.status) }}
                      </span>
                    </td>
                    <td>
                      <div v-if="bill.status === 'Forwarded' || bill.status === 'Received'" class="small text-muted">
                        <i class="bi bi-send me-1"></i>ส่งโดย: {{ bill.forwarded_by }}<br/>
                        <span class="ms-3">{{ formatDateTime(bill.forwarded_at) }}</span>
                      </div>
                      <div v-if="bill.status === 'Received'" class="small text-success mt-1">
                        <i class="bi bi-check2-all me-1"></i>รับโดย: {{ bill.received_by }}<br/>
                        <span class="ms-3">{{ formatDateTime(bill.received_at) }}</span>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-2">
                        <!-- Action Buttons based on status -->
                        <button
                          v-if="bill.status === 'Draft'"
                          class="btn btn-sm btn-success"
                          @click="forwardBill(bill)"
                          title="ส่งต่อให้บริหาร"
                        >
                          <i class="bi bi-send-check"></i>
                        </button>
                        
                        <button
                          v-if="bill.status === 'Forwarded'"
                          class="btn btn-sm btn-info text-white"
                          @click="receiveBill(bill)"
                          title="ยืนยันรับเอกสาร (บริหาร)"
                        >
                          <i class="bi bi-inbox"></i>
                        </button>

                        <button
                          v-if="bill.status === 'Draft'"
                          class="btn btn-sm btn-outline-warning"
                          @click="openForm(bill)"
                          title="แก้ไข"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                        
                        <button
                          v-if="bill.approval_file_path"
                          class="btn btn-sm btn-outline-primary"
                          @click="viewFile(bill.approval_file_path)"
                          title="ดูคำขออนุมัติซื้อ"
                        >
                          <i class="bi bi-file-earmark-check"></i>
                        </button>
                        
                        <button
                          v-if="bill.po_file_path"
                          class="btn btn-sm btn-outline-info"
                          @click="viewFile(bill.po_file_path)"
                          title="ดูใบสั่งซื้อ"
                        >
                          <i class="bi bi-file-earmark-ruled"></i>
                        </button>
                        
                        <button
                          v-if="bill.file_path"
                          class="btn btn-sm btn-outline-secondary"
                          @click="viewFile(bill.file_path)"
                          title="ดูใบวางบิล"
                        >
                          <i class="bi bi-file-earmark-text"></i>
                        </button>

                        <button
                          v-if="bill.status === 'Draft' || isAdmin"
                          class="btn btn-sm btn-outline-danger"
                          @click="deleteBill(bill)"
                          title="ลบ"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="bills.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลบิล</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProcurementForm ref="procurementForm" @saved="fetchBills" />
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import ProcurementForm from './ProcurementForm.vue';

export default {
  name: 'ProcurementDashboard',
  components: { ProcurementForm },
  data() {
    return {
      bills: [],
      userProfile: null,
      filters: {
        search: '',
        status: ''
      }
    };
  },
  computed: {
    isAdmin() {
      const dept = (this.userProfile && this.userProfile.department) || '';
      return dept.includes('กลุ่มงานสุขภาพดิจิทัล');
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchBills();
  },
  methods: {
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api-hosoffice/get_user_profile.php');
        if (response.data.status === 'success') {
          this.userProfile = response.data;
        }
      } catch (error) {
        console.error('Failed to load profile', error);
      }
    },
    async fetchBills() {
      try {
        const params = new URLSearchParams(this.filters).toString();
        const res = await axios.get(`/api-digital/procurement/get_bills.php?${params}`);
        if (res.data.status === 'success') {
          this.bills = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    openForm(bill = null) {
      this.$refs.procurementForm.open(bill);
    },
    async forwardBill(bill) {
      const result = await Swal.fire({
        title: 'ยืนยันการส่งเอกสาร?',
        text: `คุณต้องการส่งบิลเลขที่ ${bill.bill_number} ให้ฝ่ายบริหารใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ส่งเอกสาร',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#28a745'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/procurement/forward_bill.php', { id: bill.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'ส่งเอกสารสำเร็จ', showConfirmButton: false, timer: 1500 });
            this.fetchBills();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', err.response?.data?.message || 'Cannot forward bill', 'error');
        }
      }
    },
    async receiveBill(bill) {
      const result = await Swal.fire({
        title: 'ยืนยันรับเอกสาร?',
        text: `คุณได้รับบิลเลขที่ ${bill.bill_number} เรียบร้อยแล้วใช่หรือไม่?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'รับเอกสารแล้ว',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#17a2b8'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/procurement/receive_bill.php', { id: bill.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'รับเอกสารสำเร็จ', showConfirmButton: false, timer: 1500 });
            this.fetchBills();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', err.response?.data?.message || 'Cannot receive bill', 'error');
        }
      }
    },
    async deleteBill(bill) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบบิลเลขที่ ${bill.bill_number} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/procurement/delete_bill.php', { id: bill.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', showConfirmButton: false, timer: 1500 });
            this.fetchBills();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', err.response?.data?.message || 'Cannot delete bill', 'error');
        }
      }
    },
    viewFile(path) {
      if (!path) return;
      let url = path;
      if (!path.startsWith('http')) {
        const baseUrl = window.location.origin;
        url = `${baseUrl}/vue-app/vite-digital/${path}`;
      }
      window.open(url, '_blank');
    },
    getStatusClass(status) {
      switch (status) {
        case 'Draft': return 'bg-secondary';
        case 'Forwarded': return 'bg-warning text-dark';
        case 'Received': return 'bg-success';
        default: return 'bg-light text-dark';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'Draft': return 'รอดำเนินการ';
        case 'Forwarded': return 'ส่งให้บริหารแล้ว';
        case 'Received': return 'บริหารรับเอกสารแล้ว';
        default: return status;
      }
    },
    formatCurrency(value) {
      if (!value) return '0.00';
      return parseFloat(value).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      const date = new Date(dateStr);
      return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    formatDateTime(dateStr) {
      if (!dateStr) return '-';
      const date = new Date(dateStr);
      return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + 
             date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>
