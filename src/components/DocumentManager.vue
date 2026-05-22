<template>
  <div class="document-manager">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">
        <i class="bi bi-folder2-open me-2"></i> {{ title || formatCategory(category) }}
      </h2>
      <button v-if="isEditable" class="btn btn-primary btn-upload" @click="showUploadModal">
        <i class="bi bi-cloud-upload me-2"></i> อัปโหลดเอกสาร
      </button>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="files.length === 0" class="text-center py-5 empty-state">
      <i class="bi bi-inbox fs-1 text-muted"></i>
      <p class="text-muted mt-2">ไม่พบเอกสารในหมวดนี้</p>
    </div>

    <div v-else class="row g-3">
      <div v-for="file in files" :key="file.id" class="col-md-6 col-lg-4">
        <div class="card h-100 document-card shadow-sm" @click="openFile(file)">
          <div class="card-body d-flex align-items-center">
            <div class="file-icon me-3">
              <i
                v-if="isImage(file.file_name)"
                class="bi bi-file-earmark-image-fill text-success fs-1"
              ></i>
              <i v-else class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
            </div>
            <div class="file-info overflow-hidden flex-grow-1">
              <h6 class="card-title text-truncate mb-1" :title="file.file_name">
                {{ file.file_name }}
              </h6>
              <small class="text-muted">
                <i class="bi bi-calendar-event me-1"></i>
                {{ formatDate(file.uploaded_at) }}
              </small>
            </div>
            <div class="ms-2" v-if="isEditable">
              <button class="btn btn-sm btn-outline-danger" @click.stop="confirmDelete(file)">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Modal (Simulated with SweetAlert or custom modal) -->
    <!-- Using Hidden File Input for Simplicity -->
    <input
      type="file"
      ref="fileInput"
      class="d-none"
      accept="application/pdf,image/png,image/jpeg,image/jpg"
      @change="handleFileSelect"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  category: {
    type: String,
    required: true
  },
  title: {
    type: String,
    default: ''
  },
  isEditable: {
    type: Boolean,
    default: false
  }
});

const files = ref([]);
const loading = ref(false);
const fileInput = ref(null);

const formatCategory = (cat) => {
  return cat.charAt(0).toUpperCase() + cat.slice(1);
};

const isImage = (filename) => {
  if (!filename) return false;
  const ext = filename.split('.').pop().toLowerCase();
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const fetchFiles = async () => {
  loading.value = true;
  try {
    const response = await axios.get(
      `/backend/api-digital/document_center/get_files_pdf.php?category=${props.category}`
    );
    files.value = response.data;
  } catch (error) {
    console.error('Error fetching files:', error);
    Swal.fire({
      icon: 'error',
      title: 'ข้อผิดพลาด',
      text: 'ไม่สามารถโหลดข้อมูลเอกสารได้'
    });
  } finally {
    loading.value = false;
  }
};

const showUploadModal = () => {
  fileInput.value.click();
};

const handleFileSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Reset input
  event.target.value = '';

  // Confirm upload with name input
  const { value: customName, isConfirmed } = await Swal.fire({
    title: 'ระบุชื่อเอกสาร',
    html: `
      <div class="mb-3">
        <label class="form-label text-start w-100">ชื่อเอกสาร</label>
        <input id="swal-input1" class="form-control" placeholder="${file.name}">
      </div>
      <div class="text-muted small text-start">หากไม่ระบุจะใช้ชื่อไฟล์: ${file.name}</div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'ยืนยันและอัปโหลด',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#198754',
    preConfirm: () => {
      return document.getElementById('swal-input1').value;
    }
  });

  if (isConfirmed) {
    uploadFile(file, customName);
  }
};

const uploadFile = async (file, customName) => {
  const formData = new FormData();
  formData.append('pdf_file', file);
  formData.append('category', props.category);
  formData.append('custom_name', customName);

  Swal.fire({
    title: 'กำลังอัปโหลด...',
    didOpen: () => {
      Swal.showLoading();
    },
    allowOutsideClick: false
  });

  try {
    const response = await axios.post(
      '/backend/api-digital/document_center/upload_pdf.php',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    );

    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ',
        text: 'อัปโหลดเอกสารเรียบร้อยแล้ว',
        timer: 1500,
        showConfirmButton: false
      });
      fetchFiles();
    } else {
      throw new Error(response.data.error || 'Upload failed');
    }
  } catch (error) {
    console.error('Upload error:', error);
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: error.message || 'ไม่สามารถอัปโหลดไฟล์ได้'
    });
  }
};

const confirmDelete = async (file) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: `ต้องการลบไฟล์ "${file.file_name}" หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ใช่, ลบเลย',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    deleteFile(file);
  }
};

const deleteFile = async (file) => {
  Swal.fire({
    title: 'กำลังลบ...',
    didOpen: () => {
      Swal.showLoading();
    },
    allowOutsideClick: false
  });

  try {
    const response = await axios.post('/backend/api-digital/document_center/delete_pdf.php', {
      id: file.id,
      file_path: file.file_path
    });

    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: 'ลบสำเร็จ',
        timer: 1500,
        showConfirmButton: false
      });
      fetchFiles();
    } else {
      throw new Error(response.data.error || 'ลบไม่สำเร็จ');
    }
  } catch (error) {
    console.error('Delete error:', error);
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถลบไฟล์ได้'
    });
  }
};

const openFile = (file) => {
  const path = `/backend/api-digital/uploads/${encodeURIComponent(file.file_path)}`;
  window.open(path, '_blank');
};

watch(
  () => props.category,
  () => {
    fetchFiles();
  }
);

onMounted(() => {
  fetchFiles();
});
</script>

<style scoped>
.document-manager {
  background-color: #fff;
  padding: 20px;
  border-radius: 15px;
  /* box-shadow: 0 4px 6px rgba(0,0,0,0.05); */
}

.section-title {
  color: #6f42c1;
  font-weight: 700;
}

.btn-upload {
  background: linear-gradient(to right, #6f42c1, #59359a);
  border: none;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-upload:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
}

.document-card {
  transition: all 0.2s;
  border: 1px solid #eee;
  cursor: pointer;
}

.document-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1) !important;
  border-color: #6f42c1;
}

.file-icon {
  flex-shrink: 0;
}

.empty-state {
  background-color: #f8f9fa;
  border-radius: 10px;
  border: 2px dashed #dee2e6;
}
</style>
