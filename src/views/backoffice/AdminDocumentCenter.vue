<template>
  <div class="page-container min-vh-100 bg-light py-5">
    <div class="container">
      <!-- Header -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                  <li class="breadcrumb-item">
                    <router-link to="/home-backoffice">หน้าหลัก</router-link>
                  </li>
                  <li class="breadcrumb-item active" aria-current="page">จัดการเอกสาร</li>
                </ol>
              </nav>
              <h2 class="fw-bold text-dark mb-0">
                <i class="bi bi-files me-2 text-primary"></i>ศูนย์จัดการเอกสาร
              </h2>
            </div>
            <button class="btn btn-outline-secondary" @click="$router.push('/home-backoffice')">
              <i class="bi bi-arrow-left me-1"></i> กลับ
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4">
          <div class="row g-4 mb-4 align-items-end">
            <div class="col-md-6 col-lg-5">
              <label class="form-label fw-bold">เลือกหมวดหมู่เอกสาร</label>
              <select
                v-model="selectedCategory"
                class="form-select form-select-lg shadow-sm border-0 bg-light"
              >
                <option v-for="cat in categories" :key="cat.category_key" :value="cat.category_key">
                  {{ cat.category_name }}
                </option>
              </select>
            </div>
            <div class="col-md-6 col-lg-7 d-flex gap-2">
              <button class="btn btn-success" @click="addCategory">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มหมวดหมู่
              </button>
              <button 
                class="btn btn-outline-danger" 
                @click="deleteCategory" 
                :disabled="!selectedCategory || isDefaultCategory(selectedCategory)"
                title="ลบหมวดหมู่ที่เลือก (ไม่สามารถลบหมวดหมู่หลักได้)"
              >
                <i class="bi bi-trash me-1"></i> ลบ
              </button>
            </div>
          </div>

          <div class="document-area bg-light rounded-4 p-3" v-if="selectedCategory">
            <DocumentManager
              :key="selectedCategory"
              :category="selectedCategory"
              :title="getCategoryTitle(selectedCategory)"
              :is-editable="true"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import DocumentManager from '../../components/DocumentManager.vue';

const selectedCategory = ref('');
const categories = ref([]);

const defaultCategories = ['document', 'policy', 'pdpa', 'sla', 'handbook', 'certificate', 'communication'];

const isDefaultCategory = (key) => {
  return defaultCategories.includes(key);
};

const fetchCategories = async () => {
  try {
    const res = await axios.get('/backend/api-digital/document_center/get_categories.php');
    if (res.data.status === 'success') {
      categories.value = res.data.data;
      if (!selectedCategory.value && categories.value.length > 0) {
        selectedCategory.value = categories.value[0].category_key;
      }
    }
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
};

const addCategory = async () => {
  const { value: categoryName } = await Swal.fire({
    title: 'เพิ่มหมวดหมู่ใหม่',
    input: 'text',
    inputLabel: 'ชื่อหมวดหมู่เอกสาร',
    inputPlaceholder: 'ระบุชื่อหมวดหมู่ เช่น รายงานประจำปี',
    showCancelButton: true,
    confirmButtonText: 'บันทึก',
    cancelButtonText: 'ยกเลิก',
    inputValidator: (value) => {
      if (!value) {
        return 'กรุณาระบุชื่อหมวดหมู่';
      }
    }
  });

  if (categoryName) {
    try {
      const res = await axios.post('/backend/api-digital/document_center/add_category.php', {
        category_name: categoryName
      });
      if (res.data.status === 'success') {
        await fetchCategories();
        selectedCategory.value = res.data.category_key;
        Swal.fire({
          icon: 'success',
          title: 'สำเร็จ',
          text: 'เพิ่มหมวดหมู่เรียบร้อยแล้ว',
          timer: 1500,
          showConfirmButton: false
        });
      } else {
        Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถเพิ่มหมวดหมู่ได้', 'error');
      }
    } catch (error) {
      console.error(error);
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    }
  }
};

const deleteCategory = async () => {
  if (!selectedCategory.value || isDefaultCategory(selectedCategory.value)) return;

  const catObj = categories.value.find(c => c.category_key === selectedCategory.value);
  const catName = catObj ? catObj.category_name : '';

  const result = await Swal.fire({
    title: 'ยืนยันการลบหมวดหมู่',
    text: `ต้องการลบหมวดหมู่ "${catName}" หรือไม่? (ต้องไม่มีเอกสารอยู่ในหมวดหมู่นี้)`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ใช่, ลบเลย',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    try {
      const res = await axios.post('/backend/api-digital/document_center/delete_category.php', {
        category_key: selectedCategory.value
      });
      if (res.data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          timer: 1500,
          showConfirmButton: false
        });
        selectedCategory.value = '';
        await fetchCategories();
      } else {
        Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถลบหมวดหมู่ได้', 'error');
      }
    } catch (error) {
      console.error(error);
      Swal.fire('เกิดข้อผิดพลาด', error.response?.data?.message || 'ไม่สามารถลบหมวดหมู่ได้', 'error');
    }
  }
};

const getCategoryTitle = (key) => {
  const cat = categories.value.find(c => c.category_key === key);
  return cat ? `จัดการ${cat.category_name}` : 'จัดการเอกสาร';
};

onMounted(() => {
  fetchCategories();
});
</script>

<style scoped>
.page-container {
  background-color: #f8f9fa;
}

.breadcrumb-item a {
  text-decoration: none;
  color: #6c757d;
}
.breadcrumb-item a:hover {
  color: #0d6efd;
}
</style>
