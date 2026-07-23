<template>
  <div class="comm-list-container">
    <header class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
      <div>
        <h2 class="text-primary fw-bold m-0">
          <i class="bi bi-card-list me-2"></i>ทะเบียนช่องทางสื่อสาร
        </h2>
        <p class="text-muted small mb-0 mt-1">
          จัดการและประเมินผลช่องทางการสื่อสารทั้งภายในและภายนอกองค์กร
        </p>
      </div>
      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-secondary shadow-sm"
          @click="$router.push('/communication-dashboard')"
        >
          <i class="bi bi-pie-chart-fill me-1"></i> แดชบอร์ด
        </button>
        <button class="btn btn-danger shadow-sm ms-2" @click="generatePDF">
          <i class="bi bi-file-earmark-pdf me-1"></i> พิมพ์รายงาน
        </button>
      </div>
    </header>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3">
        <div class="row g-3">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"
                ><i class="bi bi-search"></i
              ></span>
              <input
                type="text"
                class="form-control border-start-0 ps-0 text-dark"
                v-model="filters.search"
                placeholder="ค้นหาตามชื่อ, วัตถุประสงค์ หรือหน่วยงาน..."
                @input="debouncedFetch"
              />
            </div>
          </div>
          <div class="col-md-3">
            <select
              class="form-select shadow-sm border-light-subtle"
              v-model="filters.category"
              @change="fetchChannels"
            >
              <option value="all">ทุกหมวดหมู่</option>
              <option value="Internal">การสื่อสารภายในองค์กร</option>
              <option value="External">การสื่อสารภายนอกองค์กร</option>
              <option value="Customer Service">บริการลูกค้าและสนับสนุน</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-secondary">
            <tr>
              <th scope="col" class="py-3 ps-4">รายละเอียดช่องทาง</th>
              <th scope="col" class="py-3">ติดต่อ / ลิงก์</th>
              <th scope="col" class="py-3">ความรับผิดชอบ</th>
              <th scope="col" class="py-3 text-center">สถานะ</th>
              <th scope="col" class="py-3 text-end pe-4">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="channelList.length === 0">
              <td colspan="5" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                ไม่พบข้อมูลช่องทางสื่อสาร
              </td>
            </tr>
            <tr v-for="ch in channelList" :key="ch.id">
              <td class="ps-4">
                <div class="fw-bold text-dark">{{ ch.channel_name }}</div>
                <div class="small text-muted mt-1">
                  <span class="badge bg-light text-dark border me-1">{{ ch.category }}</span>
                  <span class="badge text-bg-secondary">{{ ch.channel_type }}</span>
                </div>
              </td>
              <td>
                <div
                  class="fw-medium text-primary text-truncate"
                  style="max-width: 250px"
                  :title="ch.contact_detail"
                >
                  {{ ch.contact_detail || 'N/A' }}
                </div>
                <div class="small text-muted mt-1">
                  <i class="bi bi-clock me-1"></i>SLA: {{ ch.sla_response_time || '-' }}
                </div>
              </td>
              <td>
                <div class="fw-medium text-dark">
                  <i class="bi bi-person me-1"></i>{{ ch.responsible_person || 'N/A' }}
                </div>
                <div class="small text-muted mt-1">
                  <i class="bi bi-building me-1"></i>{{ ch.department || 'N/A' }}
                </div>
              </td>
              <td class="text-center">
                <span class="badge" :class="getStatusBadge(ch.status)">
                  {{ ch.status }}
                </span>
                <div class="small text-muted mt-1" v-if="ch.usage_frequency">
                  {{ ch.usage_frequency }}
                </div>
              </td>
              <td class="text-end pe-4">
                <button
                  class="btn btn-sm btn-light border shadow-sm me-2"
                  @click="openModal('edit', ch)"
                  title="ดูและแก้ไขรายละเอียด"
                >
                  <i class="bi bi-pencil-square text-primary"></i>
                </button>
                <button
                  class="btn btn-sm btn-light border shadow-sm"
                  @click="deleteChannel(ch.id)"
                  title="ลบข้อมูล"
                >
                  <i class="bi bi-trash3 text-danger"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit/Add Modal -->
    <div class="modal fade" id="channelModal" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-primary text-white pt-4 pb-3 px-4">
            <h5 class="modal-title fw-bold">
              <i
                class="bi"
                :class="modalMode === 'add' ? 'bi-plus-circle' : 'bi-pencil-square'"
              ></i>
              {{ modalMode === 'add' ? 'เพิ่มช่องทางใหม่' : 'แก้ไขรายละเอียดช่องทาง' }}
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-0 bg-light">
            <form @submit.prevent="saveChannel">
              <div class="row g-0">
                <!-- Left Column (Basic Info & Contact) -->
                <div class="col-lg-6 p-4 border-end bg-white">
                  <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">1. ข้อมูลพื้นฐาน</h6>
                  <div class="row g-3 mb-4">
                    <div class="col-12">
                      <label class="form-label form-label-sm fw-bold"
                        >ชื่อช่องทาง <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.channel_name"
                        required
                      />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold"
                        >หมวดหมู่ <span class="text-danger">*</span></label
                      >
                      <select class="form-select form-select-sm" v-model="form.category" required>
                        <option value="Internal">ภายในองค์กร</option>
                        <option value="External">ภายนอกองค์กร</option>
                        <option value="Customer Service">บริการลูกค้า/สนับสนุน</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold"
                        >ประเภทช่องทาง <span class="text-danger">*</span></label
                      >
                      <div class="d-flex gap-2">
                        <select
                          class="form-select form-select-sm"
                          v-model="form.channel_type"
                          required
                        >
                          <option value="" disabled>-- เลือกประเภท --</option>
                          <option v-for="t in channelTypes" :key="t.id" :value="t.type_name">
                            {{ t.type_name }}
                          </option>
                        </select>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary"
                          @click="addNewType"
                          title="เพิ่มประเภทใหม่"
                        >
                          <i class="bi bi-plus-lg"></i>
                        </button>
                      </div>
                    </div>
                    <div class="col-12">
                      <label class="form-label form-label-sm fw-bold">วัตถุประสงค์</label>
                      <textarea
                        class="form-control form-control-sm"
                        v-model="form.objective"
                        rows="2"
                      ></textarea>
                    </div>
                    <div class="col-12">
                      <label class="form-label form-label-sm fw-bold">กลุ่มเป้าหมาย</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.target_audience"
                      />
                    </div>
                  </div>

                  <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                    2. รายละเอียดการติดต่อผู้รับผิดชอบ
                  </h6>
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label form-label-sm fw-bold"
                        >URL / ลิงก์ / หมายเลขโทรศัพท์</label
                      >
                      <input
                        type="text"
                        class="form-control form-control-sm text-primary font-monospace"
                        v-model="form.contact_detail"
                      />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold">ผู้รับผิดชอบหลัก</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.responsible_person"
                      />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold">หน่วยงานที่ดูแล</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.department"
                      />
                    </div>
                    <div class="col-12">
                      <label class="form-label form-label-sm fw-bold">SLA / เวลาตอบกลับ</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.sla_response_time"
                        placeholder="เช่น 24 ชั่วโมง, ทันที"
                      />
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 p-4">
                  <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                    3. สถานะและการบริหารจัดการ
                  </h6>
                  <div class="row g-3 mb-4">
                    <div class="col-md-4">
                      <label class="form-label form-label-sm fw-bold">สถานะ</label>
                      <select class="form-select form-select-sm" v-model="form.status">
                        <option value="Active">ใช้งานอยู่ (Active)</option>
                        <option value="Backup">สำรอง (Backup)</option>
                        <option value="Inactive">ยกเลิก (Inactive)</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label form-label-sm fw-bold">ความถี่ในการใช้งาน</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.usage_frequency"
                        placeholder="รายวัน, รายสัปดาห์..."
                      />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label form-label-sm fw-bold">ระดับความเป็นทางการ</label>
                      <select class="form-select form-select-sm" v-model="form.formality_level">
                        <option value="">N/A</option>
                        <option value="Formal">เป็นทางการ (Formal)</option>
                        <option value="Semi-Formal">กึ่งทางการ (Semi-Formal)</option>
                        <option value="Informal">ไม่เป็นทางการ (Informal)</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label form-label-sm fw-bold"
                        >เครื่องมือหรือแพลตฟอร์มที่ใช้</label
                      >
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="form.platform_tool"
                        placeholder="เช่น MS Teams, LINE, Facebook ฯลฯ"
                      />
                    </div>
                  </div>

                  <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                    4. การประเมินประสิทธิภาพ
                  </h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold text-success"
                        ><i class="bi bi-plus-circle me-1"></i>จุดแข็ง (Strengths)</label
                      >
                      <textarea
                        class="form-control form-control-sm"
                        v-model="form.strengths"
                        rows="2"
                      ></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold text-warning"
                        ><i class="bi bi-dash-circle me-1"></i>ข้อจำกัด (Limitations)</label
                      >
                      <textarea
                        class="form-control form-control-sm"
                        v-model="form.limitations"
                        rows="2"
                      ></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold text-danger"
                        ><i class="bi bi-exclamation-triangle me-1"></i>ความเสี่ยง (Risks)</label
                      >
                      <textarea
                        class="form-control form-control-sm"
                        v-model="form.risks"
                        rows="2"
                      ></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label form-label-sm fw-bold text-info"
                        ><i class="bi bi-lightbulb me-1"></i>แนวทางปรับปรุง (Improvement
                        Plan)</label
                      >
                      <textarea
                        class="form-control form-control-sm"
                        v-model="form.improvement_plan"
                        rows="2"
                      ></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="p-4 border-top bg-white text-end rounded-bottom">
                <button
                  type="button"
                  class="btn btn-secondary shadow-sm px-4 me-2"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary shadow-sm px-4">
                  <i class="bi bi-save me-1"></i> บันทึกข้อมูล
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
import Swal from 'sweetalert2';
import bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import moment from 'moment';

moment.locale('th');

export default {
  name: 'CommunicationList',
  data() {
    return {
      channelList: [],
      channelTypes: [],
      filters: {
        search: '',
        category: 'all'
      },
      searchTimeout: null,
      modalMode: 'add',
      modalInstance: null,
      form: {
        id: null,
        channel_name: '',
        category: 'Internal',
        channel_type: '',
        objective: '',
        target_audience: '',
        contact_detail: '',
        responsible_person: '',
        department: '',
        sla_response_time: '',
        status: 'Active',
        usage_frequency: '',
        platform_tool: '',
        formality_level: '',
        strengths: '',
        limitations: '',
        risks: '',
        improvement_plan: ''
      }
    };
  },
  methods: {
    debouncedFetch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchChannels();
      }, 500);
    },
    async fetchFont(url) {
      return fetch(url)
        .then((res) => res.arrayBuffer())
        .then((buffer) => {
          let binary = '';
          const bytes = new Uint8Array(buffer);
          const len = bytes.byteLength;
          for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
          }
          return window.btoa(binary);
        });
    },
    async generatePDF() {
      Swal.fire({
        title: 'กำลังสร้าง PDF...',
        text: 'กรุณารอสักครู่ ระบบกำลังจัดเตรียมข้อมูล',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const doc = new jsPDF('landscape');
        
        const baseUrl = import.meta.env.BASE_URL.endsWith('/')
          ? import.meta.env.BASE_URL
          : import.meta.env.BASE_URL + '/';
        const fontNormal = await this.fetchFont(`${baseUrl}fonts/THSarabun.ttf`);
        const fontBold = await this.fetchFont(`${baseUrl}fonts/THSarabun Bold.ttf`);

        doc.addFileToVFS('THSarabun.ttf', fontNormal);
        doc.addFont('THSarabun.ttf', 'Sarabun', 'normal');
        doc.addFileToVFS('THSarabunBold.ttf', fontBold);
        doc.addFont('THSarabunBold.ttf', 'Sarabun', 'bold');

        doc.setFont('Sarabun', 'normal');

        const reportDate = moment();
        const thaiMonths = [
          'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
          'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];

        doc.setFontSize(22);
        doc.setFont('Sarabun', 'bold');
        doc.text('รายงานสรุปช่องทางการสื่อสารขององค์กร', 148, 20, { align: 'center' });
        
        doc.setFontSize(16);
        doc.setFont('Sarabun', 'normal');
        doc.text(`ข้อมูล ณ วันที่ ${reportDate.date()} ${thaiMonths[reportDate.month()]} ${reportDate.year() + 543}`, 148, 28, { align: 'center' });

        const head = [[
          'ลำดับ', 'ชื่อช่องทาง', 'หมวดหมู่', 'ประเภท', 
          'รายละเอียดการติดต่อ', 'ผู้รับผิดชอบ', 'หน่วยงาน', 'สถานะ'
        ]];

        const body = this.channelList.map((ch, index) => [
          index + 1,
          ch.channel_name || '-',
          ch.category || '-',
          ch.channel_type || '-',
          ch.contact_detail || '-',
          ch.responsible_person || '-',
          ch.department || '-',
          ch.status || '-'
        ]);

        autoTable(doc, {
          startY: 35,
          head: head,
          body: body,
          theme: 'grid',
          styles: {
            font: 'Sarabun',
            fontSize: 12,
            cellPadding: 2,
            textColor: [0, 0, 0],
            lineColor: [0, 0, 0],
            lineWidth: 0.1,
          },
          headStyles: {
            fillColor: [240, 240, 240],
            textColor: [0, 0, 0],
            fontStyle: 'bold',
            halign: 'center',
          },
          columnStyles: {
            0: { halign: 'center', cellWidth: 15 },
            1: { cellWidth: 40 },
            2: { cellWidth: 35 },
            3: { cellWidth: 35 },
            4: { cellWidth: 50 },
            5: { cellWidth: 35 },
            6: { cellWidth: 35 },
            7: { halign: 'center', cellWidth: 20 }
          }
        });

        doc.save(`Communication_Channels_Report_${reportDate.format('YYYYMMDD')}.pdf`);
        Swal.close();
      } catch (err) {
        console.error('PDF Generation Error:', err);
        Swal.fire('Error', 'ไม่สามารถสร้างไฟล์ PDF ได้', 'error');
      }
    },
    async fetchChannels() {
      try {
        const res = await axios.get(
          `/api-digital/communication/get_channels.php`,
          {
            params: {
              search: this.filters.search,
              category: this.filters.category
            }
          }
        );
        if (res.data.success) {
          this.channelList = res.data.data;
        }
      } catch (error) {
        console.error('API Error', error);
      }
    },
    async fetchChannelTypes() {
      try {
        const res = await axios.get(
          `/api-digital/communication/get_channel_types.php`
        );
        if (res.data.success) {
          this.channelTypes = res.data.data;
        }
      } catch (error) {
        console.error('Fetch Types Error', error);
      }
    },
    async addNewType() {
      const modalEl = document.getElementById('channelModal');
      const { value: newType } = await Swal.fire({
        title: 'เพิ่มประเภทช่องทางใหม่',
        input: 'text',
        inputLabel: 'ชื่อประเภทช่องทาง',
        inputPlaceholder: 'เช่น Line OA, Microsoft Teams...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        target: modalEl,
        inputValidator: (value) => {
          if (!value) {
            return 'กรุณาระบุชื่อประเภทช่องทาง!';
          }
        }
      });

      if (newType) {
        try {
          const res = await axios.post(
            '/api-digital/communication/add_channel_type.php',
            { type_name: newType }
          );

          if (res.data.success) {
            Swal.fire('สำเร็จ', 'เพิ่มประเภทช่องทางใหม่เรียบร้อยแล้ว', 'success');
            await this.fetchChannelTypes(); // Refresh list
            this.form.channel_type = res.data.data.type_name; // Auto-select the newly added type
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (error) {
          console.error('Add Type Error', error);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเพิ่มประเภทช่องทางได้', 'error');
        }
      }
    },
    openModal(mode, ch = null) {
      this.modalMode = mode;
      if (mode === 'edit' && ch) {
        this.form = { ...ch };
      } else {
        this.form = {
          id: null,
          channel_name: '',
          category: 'Internal',
          channel_type: '',
          objective: '',
          target_audience: '',
          contact_detail: '',
          responsible_person: '',
          department: '',
          sla_response_time: '',
          status: 'Active',
          usage_frequency: '',
          platform_tool: '',
          formality_level: '',
          strengths: '',
          limitations: '',
          risks: '',
          improvement_plan: ''
        };
      }

      if (!this.modalInstance) {
        this.modalInstance = new bootstrap.Modal(document.getElementById('channelModal'));
      }
      this.modalInstance.show();
    },
    hideModal() {
      if (this.modalInstance) {
        this.modalInstance.hide();
      }
    },
    async saveChannel() {
      try {
        const url =
          this.modalMode === 'add'
            ? '/api-digital/communication/add_channel.php'
            : '/api-digital/communication/update_channel.php';

        const res = await axios.post(url, this.form);
        if (res.data.success) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: res.data.message,
            showConfirmButton: false,
            timer: 1500
          });
          this.hideModal();
          this.fetchChannels();
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Server Error', 'error');
      }
    },
    async deleteChannel(id) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบข้อมูลช่องทางการสื่อสารนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#858c93',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post(
            '/api-digital/communication/delete_channel.php',
            { id }
          );
          if (res.data.success) {
            Swal.fire('ลบสำเร็จ!', 'ข้อมูลถูกลบเรียบร้อยแล้ว', 'success');
            this.fetchChannels();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (error) {
          console.error(error);
        }
      }
    },
    getStatusBadge(status) {
      if (status === 'Active') return 'bg-success-subtle text-success border border-success';
      if (status === 'Backup') return 'bg-warning-subtle text-warning border border-warning';
      return 'bg-secondary-subtle text-secondary border border-secondary';
    }
  },
  mounted() {
    this.fetchChannels();
    this.fetchChannelTypes();
  }
};
</script>

<style scoped>
.comm-list-container {
  padding: 1.5rem 2rem;
  background-color: #f8f9fc;
  min-height: calc(100vh - 60px);
}

.table th {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.form-label-sm {
  font-size: 0.825rem;
  color: #444;
  margin-bottom: 0.25rem;
}
</style>
