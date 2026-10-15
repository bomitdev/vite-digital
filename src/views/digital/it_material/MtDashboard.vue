<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-box-seam-fill text-orange me-2"></i>ระบบจัดการวัสดุคอมพิวเตอร์
          </h2>
          <p class="text-muted mb-0">ภาพรวมสต๊อกและการเคลื่อนไหววัสดุอุปกรณ์ IT</p>
        </div>
        <div>
          <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-2"></i>กลับหน้าหลัก
          </router-link>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="row g-4 mb-5">
        <div class="col-md-3">
          <div
            class="dashboard-card bg-primary text-white rounded-4 p-4 h-100 position-relative overflow-hidden cursor-pointer"
            @click="goTo('/material-it/stock')"
          >
            <div class="z-1 position-relative">
              <div class="d-flex justify-content-between">
                <div>
                  <p class="mb-1 text-white-50 small text-uppercase fw-bold">รายการวัสดุ (SKU)</p>
                  <h2 class="display-5 fw-bold mb-0">{{ dashboard.total_sku }}</h2>
                  <div class="mt-1 small fw-semibold text-white">
                    <i class="bi bi-wallet2 me-1"></i>มูลค่ารวม: {{ Number(dashboard.total_inventory_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                  </div>
                </div>
                <div
                  class="icon-box bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center mt-2"
                  style="width: 48px; height: 48px"
                >
                  <i class="bi bi-boxes fs-4"></i>
                </div>
              </div>
              <div class="mt-2 text-white-50 small">จัดการรายการวัสดุทั้งหมด &rarr;</div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div
            class="dashboard-card bg-danger text-white rounded-4 p-4 h-100 position-relative overflow-hidden cursor-pointer"
            @click="goTo('/material-it/stock')"
          >
            <div class="z-1 position-relative">
              <div class="d-flex justify-content-between">
                <div>
                  <p class="mb-1 text-white-50 small text-uppercase fw-bold">วัสดุใกล้หมด</p>
                  <h2 class="display-5 fw-bold mb-0">{{ dashboard.low_stock_count }}</h2>
                </div>
                <div
                  class="icon-box bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center"
                  style="width: 48px; height: 48px"
                >
                  <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
              </div>
              <div class="mt-3 text-white-50 small">ดูรายการที่ต้องสั่งซื้อ &rarr;</div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div
            class="dashboard-card bg-success text-white rounded-4 p-4 h-100 position-relative overflow-hidden cursor-pointer"
            @click="goTo('/material-it/in')"
          >
            <div class="z-1 position-relative">
              <div class="d-flex justify-content-between">
                <div>
                  <p class="mb-1 text-white-50 small text-uppercase fw-bold">
                    รับเข้าระบบ (เดือนนี้)
                  </p>
                  <h2 class="display-5 fw-bold mb-0">
                    {{ dashboard.total_in_month }} <span class="fs-6 fw-normal">ชิ้น</span>
                  </h2>
                  <div class="mt-1 small fw-semibold text-white">
                    <i class="bi bi-wallet2 me-1"></i>มูลค่ารับเข้า: {{ Number(dashboard.total_in_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                  </div>
                </div>
                <div
                  class="icon-box bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center mt-2"
                  style="width: 48px; height: 48px"
                >
                  <i class="bi bi-box-arrow-in-down fs-4"></i>
                </div>
              </div>
              <div class="mt-2 text-white-50 small">บันทึกรับเข้า &rarr;</div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div
            class="dashboard-card bg-warning text-dark rounded-4 p-4 h-100 position-relative overflow-hidden"
          >
            <div class="z-1 position-relative">
              <div class="d-flex justify-content-between">
                <div>
                  <p class="mb-1 text-dark-50 small text-uppercase fw-bold">จ่ายออก (เดือนนี้)</p>
                  <h2 class="display-5 fw-bold mb-0">
                    {{ dashboard.total_out_month }} <span class="fs-6 fw-normal">ชิ้น</span>
                  </h2>
                  <div class="mt-1 small fw-semibold text-dark">
                    <i class="bi bi-wallet2 me-1"></i>มูลค่าจ่ายออก: {{ Number(dashboard.total_out_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                  </div>
                </div>
                <div
                  class="icon-box bg-dark bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mt-2"
                  style="width: 48px; height: 48px"
                >
                  <i class="bi bi-box-arrow-up fs-4"></i>
                </div>
              </div>
              <div class="mt-2 text-dark-50 small">&nbsp;</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <!-- Quick Actions -->
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4">เมนูด่วน</h5>
              <div class="d-grid gap-3">
                <button
                  @click="goTo('/material-it/stock')"
                  class="btn btn-outline-primary text-start p-3 rounded-3 d-flex align-items-center"
                >
                  <i class="bi bi-list-ul fs-4 me-3 text-primary"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">คลังสินค้า (Stock)</h6>
                    <small class="text-muted">ดูรายการ เพิ่ม แก้ไข วัสดุคอมฯ</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/in')"
                  class="btn btn-outline-success text-start p-3 rounded-3 d-flex align-items-center"
                >
                  <i class="bi bi-plus-circle fs-4 me-3 text-success"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">รับวัสดุเข้าคลัง</h6>
                    <small class="text-muted">บันทึกการสั่งซื้อ รับของ</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/out')"
                  class="btn btn-outline-warning text-start p-3 rounded-3 d-flex align-items-center"
                >
                  <i class="bi bi-dash-circle fs-4 me-3 text-warning"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">จ่ายวัสดุออก</h6>
                    <small class="text-muted">เบิกจ่ายให้หน่วยงาน</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/report')"
                  class="btn btn-outline-info text-start p-3 rounded-3 d-flex align-items-center"
                >
                  <i class="bi bi-file-earmark-bar-graph fs-4 me-3 text-info"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">รายงาน (Report)</h6>
                    <small class="text-muted">ดูประวัติ ยอดสรุปต่างๆ</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/requests')"
                  class="btn btn-outline-secondary text-start p-3 rounded-3 d-flex align-items-center"
                >
                  <i class="bi bi-ui-checks fs-4 me-3 text-secondary"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">คำขอเบิก (Requests)</h6>
                    <small class="text-muted">พิจารณาอนุมัติ จ่ายของ</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/monthly-report')"
                  class="btn btn-outline-dark text-start p-3 rounded-3 d-flex align-items-center"
                >
                  <i class="bi bi-file-earmark-pdf fs-4 me-3 text-danger"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">รายงานประจำเดือน (PDF)</h6>
                    <small class="text-muted">พิมพ์สรุปยอดรายเดือน</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/yearly-report')"
                  class="btn btn-outline-primary text-start p-3 rounded-3 d-flex align-items-center mt-2"
                >
                  <i class="bi bi-calendar-check fs-4 me-3 text-primary"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">รายงานประจำปี (PDF)</h6>
                    <small class="text-muted">พิมพ์สรุปยอดรวมประจำปีปฏิทิน</small>
                  </div>
                </button>
                <button
                  @click="goTo('/material-it/logs')"
                  class="btn btn-outline-dark text-start p-3 rounded-3 d-flex align-items-center mt-2"
                >
                  <i class="bi bi-clock-history fs-4 me-3 text-secondary"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">ประวัติการใช้งาน (Logs)</h6>
                    <small class="text-muted">ตรวจสอบการเพิ่ม/ลบ/แก้ไขข้อมูล</small>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-8">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">การเคลื่อนไหวล่าสุด</h5>
                <button
                  @click="goTo('/material-it/report')"
                  class="btn btn-sm btn-light rounded-pill"
                >
                  ดูทั้งหมด
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>วันที่</th>
                      <th>ประเภท</th>
                      <th>วัสดุ</th>
                      <th class="text-center">จำนวน</th>
                      <th class="text-center">คงเหลือ</th>
                      <th>อ้างอิง/หน่วยงาน</th>
                      <th class="text-center">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="dashboard.recent_transactions.length === 0">
                      <td colspan="7" class="text-center py-4 text-muted">ยังไม่มีการเคลื่อนไหว</td>
                    </tr>
                    <tr v-for="t in dashboard.recent_transactions" :key="t.id">
                      <td>{{ formatDate(t.action_date) }}</td>
                      <td>
                        <span
                          v-if="t.action_type === 'IN'"
                          class="badge bg-success rounded-pill px-3"
                          >รับเข้า <i class="bi bi-arrow-down"></i
                        ></span>
                        <span v-else class="badge bg-warning text-dark rounded-pill px-3"
                          >จ่ายออก <i class="bi bi-arrow-up"></i
                        ></span>
                      </td>
                      <td>
                        <div class="fw-bold">{{ t.material_name }}</div>
                        <div class="small text-muted">{{ t.material_type || 'ไม่มีหมวดหมู่' }}</div>
                      </td>
                      <td class="text-center">
                        <span
                          class="fw-bold"
                          :class="{
                            'text-success': t.action_type === 'IN',
                            'text-warning': t.action_type === 'OUT'
                          }"
                        >
                          {{ t.action_type === 'IN' ? '+' : '-' }}{{ t.quantity }}
                        </span>
                        <div class="small text-muted" v-if="Number(t.total_price) > 0">
                          {{ Number(t.total_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                        </div>
                      </td>
                      <td class="text-center">
                        <span
                          class="badge rounded-pill px-3 py-1 fw-bold"
                          :class="Number(t.balance) <= Number(t.min_alert || 0) ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'"
                          style="font-size: 0.9rem;"
                        >
                          {{ t.balance !== null && t.balance !== undefined ? t.balance : '-' }}
                        </span>
                        <div class="small text-muted mt-1">{{ t.unit || '' }}</div>
                      </td>
                      <td>
                        <div v-if="t.action_type === 'OUT'">
                          <div>{{ t.receiver_name }}</div>
                          <div class="small text-muted"><i class="bi bi-building me-1"></i>{{ t.reference_dest }}</div>
                          <div class="small text-primary mt-1" v-if="t.user_profile_name"><i class="bi bi-person-check me-1"></i>จ่ายโดย: {{ t.user_profile_name }}</div>
                        </div>
                        <div v-else>
                          <div>{{ t.reference_dest }}</div>
                          <div class="small text-primary mt-1" v-if="t.user_profile_name"><i class="bi bi-person-down me-1"></i>รับเข้าโดย: {{ t.user_profile_name }}</div>
                        </div>
                      </td>
                      <td class="text-center">
                        <button
                          class="btn btn-sm btn-outline-primary border-0 me-1"
                          title="แก้ไขรายการ"
                          @click="openEditModal(t)"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button
                          class="btn btn-sm btn-outline-danger border-0"
                          title="ยกเลิกรายการ"
                          @click="cancelTransaction(t.id)"
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
      </div>
    </div>

    <!-- Edit Modal for Transaction from Dashboard -->
    <div
      class="modal fade"
      id="editTxDashModal"
      tabindex="-1"
      aria-labelledby="editTxDashModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold" id="editTxDashModalLabel">
              <i class="bi bi-pencil-square text-primary me-2"></i>แก้ไขรายการเคลื่อนไหว
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveEditTx">
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">ประเภท</label>
                <div>
                  <span
                    class="badge rounded-pill px-3 py-2"
                    :class="editForm.action_type === 'IN' ? 'bg-success' : 'bg-warning text-dark'"
                  >
                    {{ editForm.action_type === 'IN' ? 'รับเข้า' : 'จ่ายออก' }}
                  </span>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">วันที่ทำรายการ</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  v-model="editForm.action_date"
                  required
                />
              </div>

              <div class="mb-3" v-if="editForm.action_type === 'OUT'">
                <label class="form-label text-muted small fw-bold">ชื่อผู้รับ</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="editForm.receiver_name"
                  placeholder="เช่น สมชาย ใจดี"
                />
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">
                  {{ editForm.action_type === 'IN' ? 'แหล่งที่มา/ผู้จำหน่าย' : 'หน่วยงาน/ปลายทาง' }}
                </label>
                <input
                  type="text"
                  class="form-control"
                  v-model="editForm.reference_dest"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">จำนวน</label>
                <input
                  type="number"
                  class="form-control"
                  v-model="editForm.quantity"
                  min="1"
                  required
                />
                <div class="form-text text-danger">
                  * การแก้ไขจำนวนจะปรับยอดสต๊อกคงเหลือของวัสดุใหม่อัตโนมัติ
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">หมายเหตุ</label>
                <textarea class="form-control" v-model="editForm.note" rows="2"></textarea>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <button
                  type="button"
                  class="btn btn-light rounded-pill px-4"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                  บันทึกการแก้ไข
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtDashboard',
  data() {
    return {
      dashboard: {
        total_sku: 0,
        low_stock_count: 0,
        total_in_month: 0,
        total_out_month: 0,
        total_inventory_value: 0,
        total_in_value: 0,
        total_out_value: 0,
        recent_transactions: []
      },
      editForm: {
        id: null,
        action_type: '',
        action_date: '',
        receiver_name: '',
        reference_dest: '',
        quantity: 1,
        note: ''
      },
      editModalInstance: null
    };
  },
  methods: {
    async fetchDashboard() {
      try {
        const res = await axios.get('/api-digital/it_material/it_get_dashboard.php');
        if (res.data.status === 'success') {
          this.dashboard = res.data.data;
        }
      } catch (err) {
        console.error('Error fetching dashboard:', err);
      }
    },
    goTo(path) {
      this.$router.push(path);
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      return moment(dateStr).format('DD/MM/YYYY HH:mm');
    },
    openEditModal(item) {
      this.editForm = {
        id: item.id,
        action_type: item.action_type,
        action_date: moment(item.action_date).format('YYYY-MM-DDTHH:mm'),
        receiver_name: item.receiver_name || '',
        reference_dest: item.reference_dest || '',
        quantity: item.quantity,
        note: item.note || ''
      };
      if (!this.editModalInstance) {
        this.editModalInstance = new bootstrap.Modal(document.getElementById('editTxDashModal'));
      }
      this.editModalInstance.show();
    },
    async saveEditTx() {
      try {
        const res = await axios.post(
          '/api-digital/it_material/it_update_transaction.php',
          this.editForm
        );
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message,
            icon: 'success',
            confirmButtonText: 'ตกลง'
          });
          this.editModalInstance.hide();
          this.fetchDashboard();
        } else {
          Swal.fire('ข้อผิดพลาด!', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด!', 'ระบบขัดข้อง', 'error');
      }
    },
    cancelTransaction(id) {
      Swal.fire({
        title: 'ยืนยันการยกเลิกรายการ?',
        text: 'หากยกเลิกรายการนี้ ยอดวัสดุจะถูกคำนวณย้อนกลับอัตโนมัติ คุณต้องการดำเนินการต่อหรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันยกเลิกรายการ',
        cancelButtonText: 'ปิด'
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const res = await axios.post('/api-digital/it_material/it_delete_transaction.php', {
              id: id
            });
            if (res.data.status === 'success') {
              Swal.fire({
                title: 'ยกเลิกสำเร็จ!',
                text: res.data.message,
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'ตกลง'
              });
              this.fetchDashboard();
            } else {
              Swal.fire('ข้อผิดพลาด!', res.data.message, 'error');
            }
          } catch (err) {
            Swal.fire('ข้อผิดพลาด!', 'ระบบขัดข้อง', 'error');
          }
        }
      });
    }
  },
  mounted() {
    this.fetchDashboard();
  }
};
</script>

<style scoped>
.text-orange {
  color: #fd7e14;
}
.dashboard-card {
  transition: transform 0.3s ease;
}
.dashboard-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.cursor-pointer {
  cursor: pointer;
}
.text-dark-50 {
  color: rgba(33, 37, 41, 0.5);
}
</style>
