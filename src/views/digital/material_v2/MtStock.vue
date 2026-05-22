<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/material-v2">หน้าหลักวัสดุ</router-link>
              </li>
              <li class="breadcrumb-item active" aria-current="page">คลังสินค้า (Stock)</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-dark mb-0">รายการคลังวัสดุ</h2>
        </div>
        <div>
          <button class="btn btn-primary rounded-pill px-4" @click="openModal()">
            <i class="bi bi-plus-lg me-2"></i>เพิ่มวัสดุใหม่
          </button>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"
                  ><i class="bi bi-search"></i
                ></span>
                <input
                  type="text"
                  class="form-control border-start-0"
                  v-model="search"
                  placeholder="ค้นหารหัส, ชื่อ, ประเภท..."
                  @keyup.enter="fetchMaterials"
                />
                <button class="btn btn-primary" @click="fetchMaterials">ค้นหา</button>
              </div>
            </div>
            <div class="col-md-6 d-flex align-items-center">
              <div class="form-check form-switch ms-md-4">
                <input
                  class="form-check-input"
                  type="checkbox"
                  role="switch"
                  id="lowStockSwitch"
                  v-model="lowStockOnly"
                  @change="fetchMaterials"
                />
                <label class="form-check-label" for="lowStockSwitch"
                  >แสดงเฉพาะของใกล้หมด (<i class="bi bi-circle-fill text-danger small"></i>)</label
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">รหัส</th>
                  <th>ชื่ออุปกรณ์</th>
                  <th>ประเภท</th>
                  <th>ราคาต่อหน่วย</th>
                  <th class="text-center">คงเหลือ</th>
                  <th>หน่วย</th>
                  <th class="text-center">แจ้งเตือน(ขั้นต่ำ)</th>
                  <th class="text-end pe-4">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="materials.length === 0">
                  <td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลวัสดุ</td>
                </tr>
                <tr
                  v-for="item in materials"
                  :key="item.id"
                  :class="{ 'table-danger bg-opacity-10': item.balance <= item.min_alert }"
                >
                  <td class="ps-4 fw-bold">{{ item.code }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <i
                        v-if="item.balance <= item.min_alert"
                        class="bi bi-exclamation-circle-fill text-danger me-2"
                        title="ของใกล้หมด"
                      ></i>
                      {{ item.name }}
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-secondary rounded-pill">{{ item.type }}</span>
                  </td>
                  <td>{{ item.price_per_unit || '0.00' }} ฿</td>
                  <td class="text-center">
                    <span
                      class="fs-5 fw-bold"
                      :class="item.balance <= item.min_alert ? 'text-danger' : 'text-success'"
                      >{{ item.balance }}</span
                    >
                  </td>
                  <td>{{ item.unit }}</td>
                  <td class="text-center">{{ item.min_alert }}</td>
                  <td class="text-end pe-4">
                    <button
                      class="btn btn-sm btn-outline-primary rounded-circle me-2"
                      @click="openModal(item)"
                      title="แก้ไข"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <!-- Allow delete for admins if no history, or just generally but confirm -->
                    <button
                      class="btn btn-sm btn-outline-danger rounded-circle"
                      @click="deleteItem(item.id, item.name)"
                      title="ลบ"
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

      <!-- Modal Add/Edit Material -->
      <div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold">
                {{ form.id ? 'แก้ไขข้อมูลวัสดุ' : 'เพิ่มวัสดุใหม่' }}
              </h5>
              <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body p-4">
              <form @submit.prevent="saveMaterial">
                <div class="mb-3">
                  <label class="form-label"
                    >รหัสสินค้า (SKU) <span class="text-danger">*</span></label
                  >
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.code"
                    required
                    placeholder="เช่น IT-RAM-001"
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">ชื่ออุปกรณ์ <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.name"
                    required
                    placeholder="เช่น RAM DDR4 8GB"
                  />
                </div>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.type"
                      required
                      placeholder="เช่น RAM"
                    />
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">หน่วยนับ <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.unit"
                      required
                      placeholder="เช่น ชิ้น, กล่อง"
                    />
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label"
                      >ราคาต่อหน่วย <span class="text-danger">*</span></label
                    >
                    <input
                      type="number"
                      step="0.01"
                      class="form-control"
                      v-model="form.price_per_unit"
                      required
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">จำนวนขั้นต่ำแจ้งเตือน</label>
                    <input type="number" class="form-control" v-model="form.min_alert" min="0" />
                  </div>
                  <div class="col-md-6 mb-3" v-if="!form.id">
                    <label class="form-label">จำนวนยกยอดสต๊อกเริ่มต้น</label>
                    <input type="number" class="form-control" v-model="form.balance" min="0" />
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                  <button
                    type="button"
                    class="btn btn-light rounded-pill px-4 me-2"
                    data-bs-dismiss="modal"
                  >
                    ยกเลิก
                  </button>
                  <button type="submit" class="btn btn-primary rounded-pill px-4">
                    บันทึกข้อมูล
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtStock',
  data() {
    return {
      search: '',
      lowStockOnly: false,
      materials: [],
      form: {
        id: null,
        code: '',
        name: '',
        type: '',
        unit: '',
        price_per_unit: 0.0,
        min_alert: 5,
        balance: 0
      },
      modalInstance: null
    };
  },
  methods: {
    async fetchMaterials() {
      try {
        let url = `/api-digital/material_v2/get_materials.php?search=${encodeURIComponent(this.search)}`;
        if (this.lowStockOnly) url += '&low_stock=1';

        const res = await axios.get(url);
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    openModal(item = null) {
      if (item) {
        this.form = { ...item };
      } else {
        this.form = {
          id: null,
          code: '',
          name: '',
          type: '',
          unit: '',
          price_per_unit: 0.0,
          min_alert: 5,
          balance: 0
        };
      }
      if (!this.modalInstance) {
        this.modalInstance = new bootstrap.Modal(document.getElementById('materialModal'));
      }
      this.modalInstance.show();
    },
    async saveMaterial() {
      try {
        const res = await axios.post('/api-digital/material_v2/save_material.php', this.form);
        if (res.data.status === 'success') {
          // alert(res.data.message);
          this.modalInstance.hide();
          this.fetchMaterials();
        } else {
          alert(res.data.message);
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
      }
    },
    async deleteItem(id, name) {
      if (
        confirm(
          `คุณแน่ใจหรือไม่ว่าต้องการลบวัสดุ: ${name} ?\n(หากมีการทำรายการไปแล้วประวัติทั้งหมดอาจถูกลบด้วย)`
        )
      ) {
        try {
          const res = await axios.post('/api-digital/material_v2/delete_material.php', { id });
          if (res.data.status === 'success') {
            this.fetchMaterials();
          } else {
            alert(res.data.message);
          }
        } catch (err) {
          alert('ไม่สามารถลบข้อมูลได้ อาจมีการเชื่อมโยงอยู่');
        }
      }
    }
  },
  mounted() {
    this.fetchMaterials();
  }
};
</script>

<style scoped>
.breadcrumb a {
  text-decoration: none;
  color: #0d6efd;
}
.table-danger {
  --bs-table-bg: rgba(220, 53, 69, 0.05);
}
</style>
