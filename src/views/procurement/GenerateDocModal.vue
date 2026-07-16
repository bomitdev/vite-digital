<template>
  <div class="modal fade" id="generateDocModal" tabindex="-1" ref="modal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header bg-primary text-white py-3">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i>จัดทำเอกสารพัสดุ (ตราครุฑ)
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body p-4 bg-light" v-if="bill">
          <div class="alert alert-info border-0 rounded-3 shadow-sm d-flex align-items-center">
            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
            <div>
              <strong>อ้างอิงบิล:</strong> {{ bill.bill_number }}<br/>
              <small>กรุณากรอกข้อมูลเพื่อใช้ในการออกเอกสาร บันทึกข้อความ, ประกาศ, ใบสั่งซื้อ และ ใบตรวจรับ</small>
            </div>
          </div>

          <form @submit.prevent="saveData">
            <!-- Row 1 -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">วันที่ออกเอกสาร</label>
                <input type="date" class="form-control rounded-3" v-model="formData.doc_date" required />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">เรียน (ผู้มีอำนาจอนุมัติ)</label>
                <input type="text" class="form-control rounded-3" v-model="formData.to_person" placeholder="เช่น ผู้ว่าราชการจังหวัดอำนาจเจริญ" required />
              </div>
            </div>

            <!-- Row 2 -->
            <div class="row g-3 mb-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">เหตุผลความจำเป็น</label>
                <textarea class="form-control rounded-3" v-model="formData.reason" rows="2" placeholder="เพื่อให้การสนับสนุนในการรักษา การบริการผู้ป่วย..."></textarea>
              </div>
            </div>

            <!-- Row 3 -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">วงเงินงบประมาณ (บาท)</label>
                <input type="number" step="0.01" class="form-control rounded-3" v-model="formData.budget" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">กำหนดส่งมอบภายใน (วัน)</label>
                <input type="number" class="form-control rounded-3" v-model="formData.delivery_days" />
              </div>
            </div>

            <hr>
            
            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-people-fill me-2"></i>ข้อมูลคณะกรรมการตรวจรับ</h6>
            
            <div class="row g-3 mb-2" v-for="(member, index) in formData.committee" :key="index">
              <div class="col-md-5">
                <input type="text" class="form-control form-control-sm rounded-3" v-model="member.name" placeholder="ชื่อ-นามสกุล" required />
              </div>
              <div class="col-md-4">
                <input type="text" class="form-control form-control-sm rounded-3" v-model="member.position" placeholder="ตำแหน่งวิชาชีพ" required />
              </div>
              <div class="col-md-2">
                <select class="form-select form-select-sm rounded-3" v-model="member.role" required>
                  <option value="ประธานกรรมการ">ประธาน</option>
                  <option value="กรรมการ">กรรมการ</option>
                </select>
              </div>
              <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCommittee(index)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
            <div class="mb-4">
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" @click="addCommittee">
                <i class="bi bi-plus me-1"></i>เพิ่มกรรมการ
              </button>
            </div>

            <hr>

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shop me-2"></i>ข้อมูลผู้ขาย/บริษัท</h6>
            <div class="row g-3 mb-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">ที่อยู่ร้านค้า (เพื่อแสดงในใบสั่งซื้อ)</label>
                <input type="text" class="form-control rounded-3" v-model="formData.vendor_address" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">เลขประจำตัวผู้เสียภาษี</label>
                <input type="text" class="form-control rounded-3" v-model="formData.vendor_tax_id" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                <input type="text" class="form-control rounded-3" v-model="formData.vendor_tel" />
              </div>
            </div>
            
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">ชื่อผู้ว่าจ้าง (ผู้เซ็นใบสั่งซื้อ)</label>
                <input type="text" class="form-control rounded-3" v-model="formData.buyer_name" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">ตำแหน่ง</label>
                <input type="text" class="form-control rounded-3" v-model="formData.buyer_position" />
              </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <div>
                <!-- Buttons to print specific forms -->
                <button type="button" class="btn btn-outline-success me-2 mt-2" @click="printDoc('memo')" :disabled="!isSaved">
                  <i class="bi bi-printer me-1"></i>บันทึกข้อความ
                </button>
                <button type="button" class="btn btn-outline-success me-2 mt-2" @click="printDoc('winner')" :disabled="!isSaved">
                  <i class="bi bi-printer me-1"></i>ประกาศผู้ชนะ
                </button>
                <button type="button" class="btn btn-outline-success me-2 mt-2" @click="printDoc('po')" :disabled="!isSaved">
                  <i class="bi bi-printer me-1"></i>ใบสั่งซื้อ
                </button>
                <button type="button" class="btn btn-outline-success me-2 mt-2" @click="printDoc('inspection')" :disabled="!isSaved">
                  <i class="bi bi-printer me-1"></i>ใบตรวจรับ
                </button>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-save me-1" v-else></i>บันทึกข้อมูล
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'GenerateDocModal',
  data() {
    return {
      modalInstance: null,
      bill: null,
      saving: false,
      isSaved: false, // track if data has been saved to DB so we can allow printing
      formData: {
        doc_date: '',
        to_person: 'ผู้ว่าราชการจังหวัดอำนาจเจริญ',
        reason: 'เพื่อให้การสนับสนุนในการรักษา การบริการผู้ป่วยที่มารับบริการ',
        budget: 0,
        delivery_days: 15,
        vendor_address: '',
        vendor_tax_id: '',
        vendor_tel: '',
        buyer_name: '',
        buyer_position: 'นายแพทย์ชำนาญการ รักษาการในตำแหน่งผู้อำนวยการโรงพยาบาล',
        committee: [
          { name: '', position: 'พยาบาลวิชาชีพชำนาญการ', role: 'ผู้ตรวจรับพัสดุ' }
        ]
      }
    };
  },
  mounted() {
    this.modalInstance = new Modal(this.$refs.modal);
    this.$refs.modal.addEventListener('hidden.bs.modal', () => {
      this.bill = null;
      this.isSaved = false;
    });
  },
  methods: {
    async open(bill) {
      this.bill = bill;
      // Pre-fill amount
      this.formData.budget = bill.amount;
      
      // Attempt to load existing data
      try {
        const res = await axios.get(`/api-digital/procurement/document_data.php?bill_id=${bill.id}`);
        if (res.data.status === 'success' && res.data.data) {
          const d = res.data.data;
          this.formData = {
            doc_date: d.doc_date || '',
            to_person: d.to_person || this.formData.to_person,
            reason: d.reason || this.formData.reason,
            budget: d.budget || bill.amount,
            delivery_days: d.delivery_days || 15,
            vendor_address: d.vendor_address || '',
            vendor_tax_id: d.vendor_tax_id || '',
            vendor_tel: d.vendor_tel || '',
            buyer_name: d.buyer_name || '',
            buyer_position: d.buyer_position || this.formData.buyer_position,
            committee: (d.committee && d.committee.length > 0) ? d.committee : this.formData.committee
          };
          this.isSaved = true;
        } else {
          // Defaults if no record
          this.formData.doc_date = new Date().toISOString().split('T')[0];
          this.isSaved = false;
        }
      } catch (err) {
        console.error(err);
      }
      
      this.modalInstance.show();
    },
    addCommittee() {
      this.formData.committee.push({ name: '', position: 'พยาบาลวิชาชีพ', role: 'กรรมการ' });
    },
    removeCommittee(index) {
      this.formData.committee.splice(index, 1);
    },
    async saveData() {
      this.saving = true;
      try {
        const payload = { ...this.formData, bill_id: this.bill.id };
        const res = await axios.post('/api-digital/procurement/document_data.php', payload);
        if (res.data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false });
          this.isSaved = true;
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error', err.message, 'error');
      } finally {
        this.saving = false;
      }
    },
    printDoc(type) {
      if (!this.isSaved) return;
      // Navigate to the respective print view
      const routeUrl = this.$router.resolve({
        path: `/procurement/print/${type}/${this.bill.id}`
      });
      window.open(routeUrl.href, '_blank');
    }
  }
};
</script>
