<template>
  <div class="moph-key-manager">
    <!-- Security Banner -->
    <div class="alert alert-success-subtle border border-success-subtle bg-success-subtle text-success-emphasis rounded-3 mb-3 py-2 px-3">
      <div class="small">
        <i class="bi bi-shield-lock-fill me-1"></i>
        <strong>ปลอดภัยต่อการอัปขึ้น GitHub:</strong> คีย์ MOPH Notify ถูกบันทึกไว้ในตาราง
        <code>moph_notify_channels</code> (ฐานข้อมูล <code>digital</code>) โดยไม่เก็บลงในไฟล์โค้ด (จำกัดเฉพาะสิทธิ์ <code>administrator</code>)
      </div>
    </div>

    <!-- Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div class="small text-muted">
        จำนวนช่องทางทั้งหมด: <strong class="text-dark">{{ channels.length }}</strong> ช่องทาง
      </div>
      <div class="d-flex gap-2">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary rounded-pill px-3"
          @click="loadChannels"
          :disabled="loading"
        >
          <i class="bi bi-arrow-clockwise me-1"></i> รีเฟรช
        </button>
        <button
          type="button"
          class="btn btn-sm btn-success rounded-pill px-3 fw-bold"
          @click="startCreate"
          :disabled="loading"
        >
          <i class="bi bi-plus-lg me-1"></i> เพิ่มช่องทาง / คีย์ใหม่
        </button>
      </div>
    </div>

    <!-- Add / Edit Form -->
    <div v-if="showForm" class="card border-success border-opacity-50 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-success-subtle text-success-emphasis py-2 px-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold small">
          <i class="bi bi-key-fill me-1"></i>
          {{ form.id ? `แก้ไขคีย์ช่องทาง: ${form.channel_name || form.channel_code}` : 'เพิ่มช่องทาง MOPH Notify ใหม่' }}
        </span>
        <button type="button" class="btn-close btn-sm" @click="cancelForm"></button>
      </div>
      <div class="card-body p-3">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-bold mb-1">
              รหัสช่องทาง (Channel Code) <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              class="form-control form-control-sm font-monospace"
              v-model.trim="form.channel_code"
              :disabled="form.is_system"
              placeholder="เช่น opdcard, line_er"
            />
            <div class="form-text" style="font-size: 0.72rem;">
              {{ form.is_system ? 'ช่องทางหลักของระบบ (แก้ไขรหัสไม่ได้)' : 'ภาษาอังกฤษตัวพิมพ์เล็ก a-z, ตัวเลข 0-9 หรือ _' }}
            </div>
          </div>

          <div class="col-md-5">
            <label class="form-label small fw-bold mb-1">
              ชื่อกลุ่ม LINE / ช่องทาง <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              class="form-control form-control-sm"
              v-model.trim="form.channel_name"
              placeholder="เช่น กลุ่มห้องบัตร (OPD Card)"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">ลำดับแสดงผล</label>
            <input
              type="number"
              class="form-control form-control-sm"
              v-model.number="form.sort_order"
              min="1"
              max="9999"
            />
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold mb-1">คำอธิบาย</label>
            <input
              type="text"
              class="form-control form-control-sm"
              v-model.trim="form.description"
              placeholder="ระบุวัตถุประสงค์การใช้งานของกลุ่ม LINE นี้"
            />
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold mb-1">MOPH Notify Endpoint URL</label>
            <input
              type="url"
              class="form-control form-control-sm font-monospace"
              v-model.trim="form.notify_url"
              :placeholder="defaultNotifyUrl"
            />
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold mb-1">Client Key</label>
            <input
              type="text"
              class="form-control form-control-sm font-monospace"
              v-model.trim="form.client_key"
              :disabled="form.clear_keys"
              :placeholder="form.id && form.has_keys ? `คีย์ปัจจุบัน: ${form.client_key_masked} (เว้นว่างเพื่อใช้คีย์เดิม)` : 'วาง client-key จาก MOPH Notify'"
            />
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold mb-1">Secret Key</label>
            <div class="input-group input-group-sm">
              <input
                :type="showSecret ? 'text' : 'password'"
                class="form-control font-monospace"
                v-model.trim="form.secret_key"
                :disabled="form.clear_keys"
                :placeholder="form.id && form.has_keys ? `คีย์ปัจจุบัน: ${form.secret_key_masked} (เว้นว่างเพื่อใช้คีย์เดิม)` : 'วาง secret-key จาก MOPH Notify'"
                autocomplete="new-password"
              />
              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="showSecret = !showSecret"
                :title="showSecret ? 'ซ่อนคีย์' : 'แสดงคีย์ที่พิมพ์'"
              >
                <i class="bi" :class="showSecret ? 'bi-eye-slash' : 'bi-eye'"></i>
              </button>
            </div>
          </div>

          <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2 pt-1">
            <div class="d-flex flex-wrap align-items-center gap-3">
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="mophChannelActive" v-model="form.is_active" />
                <label class="form-check-label small fw-bold" for="mophChannelActive">เปิดใช้งานช่องทางนี้</label>
              </div>
              <div v-if="form.id && form.has_db_keys" class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="mophClearKeys" v-model="form.clear_keys" />
                <label class="form-check-label small text-danger" for="mophClearKeys">
                  ล้างคีย์ที่บันทึกไว้ในฐานข้อมูล
                </label>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="cancelForm">
                ยกเลิก
              </button>
              <button
                type="button"
                class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold"
                @click="testDraftChannel"
                :disabled="testingKey === 'draft' || saving"
              >
                <span v-if="testingKey === 'draft'" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-send-check me-1"></i> ทดสอบส่งคีย์นี้
              </button>
              <button
                type="button"
                class="btn btn-sm btn-success rounded-pill px-3 fw-bold"
                @click="saveChannel"
                :disabled="saving"
              >
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-save me-1"></i> บันทึกลงฐานข้อมูล
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Channel List -->
    <div v-if="loading && channels.length === 0" class="text-center py-4 text-muted small">
      <div class="spinner-border spinner-border-sm text-success me-2"></div> กำลังโหลดรายการคีย์ MOPH Notify...
    </div>

    <div v-else class="table-responsive border rounded-3 bg-white">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th>ช่องทาง / กลุ่ม LINE</th>
            <th>แหล่งข้อมูลคีย์</th>
            <th>คีย์ปัจจุบัน (Masked)</th>
            <th class="text-center">สถานะ</th>
            <th class="text-end">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ch in channels" :key="ch.id" :class="{ 'table-secondary opacity-75': !ch.is_active }">
            <td>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-bold text-dark">{{ ch.channel_name }}</span>
                <code class="badge bg-light text-secondary border">{{ ch.channel_code }}</code>
                <span v-if="ch.is_system" class="badge bg-primary-subtle text-primary border border-primary-subtle">ระบบหลัก</span>
              </div>
              <div v-if="ch.description" class="text-muted mt-1" style="font-size: 0.76rem;">
                {{ ch.description }}
              </div>
            </td>
            <td>
              <span v-if="ch.key_source === 'database'" class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-database-check me-1"></i> ฐานข้อมูล (digital)
              </span>
              <span v-else-if="ch.key_source === 'env'" class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                <i class="bi bi-file-earmark-code me-1"></i> ค่าเริ่มต้น (.env)
              </span>
              <span v-else class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                <i class="bi bi-exclamation-circle me-1"></i> ยังไม่ตั้งคีย์
              </span>
            </td>
            <td class="font-monospace" style="font-size: 0.75rem;">
              <div v-if="ch.has_keys">
                <div><span class="text-muted">CK:</span> {{ ch.client_key_masked }}</div>
                <div><span class="text-muted">SK:</span> {{ ch.secret_key_masked }}</div>
              </div>
              <span v-else class="text-muted">-</span>
            </td>
            <td class="text-center">
              <button
                type="button"
                class="btn btn-sm rounded-pill px-2 py-0"
                :class="ch.is_active ? 'btn-success' : 'btn-outline-secondary'"
                @click="toggleChannelActive(ch)"
                :title="ch.is_active ? 'คลิกเพื่อปิดการใช้งานชั่วคราว' : 'คลิกเพื่อเปิดใช้งาน'"
              >
                {{ ch.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
              </button>
            </td>
            <td class="text-end text-nowrap">
              <div class="btn-group btn-group-sm">
                <button
                  type="button"
                  class="btn btn-outline-success"
                  @click="testSavedChannel(ch)"
                  :disabled="!ch.has_keys || testingKey === ch.channel_code"
                  title="ทดสอบส่งข้อความเข้ากลุ่ม LINE นี้"
                >
                  <span v-if="testingKey === ch.channel_code" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="bi bi-send-check"></i>
                </button>
                <button
                  type="button"
                  class="btn btn-outline-primary"
                  @click="startEdit(ch)"
                  title="แก้ไขชื่อ / คีย์ MOPH Notify"
                >
                  <i class="bi bi-pencil-square"></i>
                </button>
                <button
                  v-if="!ch.is_system"
                  type="button"
                  class="btn btn-outline-danger"
                  @click="deleteChannel(ch)"
                  title="ลบช่องทาง"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="channels.length === 0">
            <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลช่องทาง MOPH Notify</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'MophNotifyKeyManager',
  emits: ['channels-updated'],
  data() {
    return {
      loading: false,
      saving: false,
      testingKey: '',
      showForm: false,
      showSecret: false,
      defaultNotifyUrl: 'https://morpromt2f.moph.go.th/api/notify/send',
      channels: [],
      form: this.createEmptyForm()
    };
  },
  mounted() {
    this.loadChannels();
  },
  methods: {
    createEmptyForm() {
      return {
        id: 0,
        channel_code: '',
        channel_name: '',
        description: '',
        notify_url: this.defaultNotifyUrl || 'https://morpromt2f.moph.go.th/api/notify/send',
        client_key: '',
        secret_key: '',
        is_active: true,
        is_system: false,
        has_db_keys: false,
        has_keys: false,
        client_key_masked: '',
        secret_key_masked: '',
        clear_keys: false,
        sort_order: 100
      };
    },
    getAuthHeaders() {
      const token = localStorage.getItem('user_token');
      return { Authorization: `Bearer ${token}` };
    },
    async loadChannels() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/settings/moph_notify.php', {
          headers: this.getAuthHeaders()
        });
        if (res.data && res.data.status === 'success') {
          this.defaultNotifyUrl = res.data.default_notify_url || this.defaultNotifyUrl;
          this.channels = res.data.channels || [];
        }
      } catch (err) {
        console.error('Error loading MOPH channels:', err);
        Swal.fire('ผิดพลาด', err.response?.data?.message || 'ไม่สามารถโหลดข้อมูลคีย์ MOPH Notify ได้', 'error');
      } finally {
        this.loading = false;
      }
    },
    startCreate() {
      this.showSecret = false;
      this.form = this.createEmptyForm();
      this.showForm = true;
    },
    startEdit(ch) {
      this.showSecret = false;
      this.form = {
        id: ch.id,
        channel_code: ch.channel_code,
        channel_name: ch.channel_name,
        description: ch.description || '',
        notify_url: ch.notify_url || this.defaultNotifyUrl,
        client_key: '',
        secret_key: '',
        is_active: Boolean(ch.is_active),
        is_system: Boolean(ch.is_system),
        has_db_keys: Boolean(ch.has_db_keys),
        has_keys: Boolean(ch.has_keys),
        client_key_masked: ch.client_key_masked || '',
        secret_key_masked: ch.secret_key_masked || '',
        clear_keys: false,
        sort_order: ch.sort_order || 100
      };
      this.showForm = true;
    },
    cancelForm() {
      this.showForm = false;
      this.showSecret = false;
      this.form = this.createEmptyForm();
    },
    async saveChannel() {
      if (!this.form.channel_name) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อกลุ่ม LINE / ช่องทาง', 'warning');
        return;
      }
      if (!this.form.id && !/^[a-z0-9_]{2,64}$/.test(this.form.channel_code)) {
        Swal.fire(
          'รหัสไม่ถูกต้อง',
          'รหัสช่องทางต้องเป็นตัวพิมพ์เล็ก a-z, ตัวเลข 0-9 หรือ _ (2-64 ตัวอักษร)',
          'warning'
        );
        return;
      }

      this.saving = true;
      try {
        const res = await axios.post(
          '/api-digital/settings/moph_notify.php',
          {
            action: 'save_channel',
            ...this.form
          },
          { headers: this.getAuthHeaders() }
        );
        if (res.data && res.data.status === 'success') {
          this.channels = res.data.channels || [];
          this.cancelForm();
          this.$emit('channels-updated', this.channels);
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: res.data.message,
            timer: 1600,
            showConfirmButton: false
          });
        } else {
          Swal.fire('ผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ผิดพลาด', err.response?.data?.message || 'ไม่สามารถบันทึกคีย์ MOPH Notify ได้', 'error');
      } finally {
        this.saving = false;
      }
    },
    async toggleChannelActive(ch) {
      try {
        const res = await axios.post(
          '/api-digital/settings/moph_notify.php',
          {
            action: 'toggle_active',
            id: ch.id,
            is_active: !ch.is_active
          },
          { headers: this.getAuthHeaders() }
        );
        if (res.data && res.data.status === 'success') {
          this.channels = res.data.channels || [];
          this.$emit('channels-updated', this.channels);
        }
      } catch (err) {
        Swal.fire('ผิดพลาด', err.response?.data?.message || 'ไม่สามารถเปลี่ยนสถานะช่องทางได้', 'error');
      }
    },
    async deleteChannel(ch) {
      const confirmRes = await Swal.fire({
        title: `ลบช่องทาง "${ch.channel_name}"?`,
        text: 'การลบจะเอาคีย์กลุ่ม LINE นี้ออกจากฐานข้อมูล digital',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบช่องทาง',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc3545'
      });
      if (!confirmRes.isConfirmed) return;

      try {
        const res = await axios.post(
          '/api-digital/settings/moph_notify.php',
          {
            action: 'delete_channel',
            id: ch.id
          },
          { headers: this.getAuthHeaders() }
        );
        if (res.data && res.data.status === 'success') {
          this.channels = res.data.channels || [];
          this.$emit('channels-updated', this.channels);
          Swal.fire({
            icon: 'success',
            title: 'ลบเรียบร้อยแล้ว',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
        }
      } catch (err) {
        Swal.fire('ผิดพลาด', err.response?.data?.message || 'ไม่สามารถลบช่องทางได้', 'error');
      }
    },
    async runTestRequest(payload, keyTag) {
      this.testingKey = keyTag;
      try {
        const res = await axios.post(
          '/api-digital/settings/moph_notify.php',
          {
            action: 'test_channel',
            ...payload
          },
          { headers: this.getAuthHeaders() }
        );
        if (res.data && res.data.status === 'success') {
          Swal.fire('ทดสอบส่งสำเร็จ!', res.data.message, 'success');
        } else {
          Swal.fire('ทดสอบส่งไม่สำเร็จ', res.data?.message || 'เกิดข้อผิดพลาดจาก MOPH Notify', 'error');
        }
      } catch (err) {
        Swal.fire('ผิดพลาด', err.response?.data?.message || 'ไม่สามารถทดสอบส่งได้', 'error');
      } finally {
        this.testingKey = '';
      }
    },
    testSavedChannel(ch) {
      this.runTestRequest({ id: ch.id, channel_code: ch.channel_code }, ch.channel_code);
    },
    testDraftChannel() {
      this.runTestRequest(
        {
          id: this.form.id || 0,
          channel_code: this.form.channel_code,
          channel_name: this.form.channel_name,
          notify_url: this.form.notify_url,
          client_key: this.form.client_key,
          secret_key: this.form.secret_key
        },
        'draft'
      );
    }
  }
};
</script>

<style scoped>
.moph-key-manager code {
  font-size: 0.78rem;
}
</style>

