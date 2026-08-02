<template>
  <div class="container-fluid">
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <h1 class="mfc fw-bold">{{ categoryName || 'เอกสาร' }}</h1>
      </div>
      <hr />

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <DocumentManager v-else :category="categoryKey" :title="categoryName" />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import DocumentManager from '../../components/DocumentManager.vue';

const route = useRoute();
const categoryKey = ref(route.params.category);
const categoryName = ref('');
const loading = ref(true);

const fetchCategoryName = async () => {
  loading.value = true;
  categoryKey.value = route.params.category;
  
  try {
    const res = await axios.get('/backend/api-digital/document_center/get_categories.php');
    if (res.data.status === 'success') {
      const cat = res.data.data.find(c => c.category_key === categoryKey.value);
      if (cat) {
        categoryName.value = cat.category_name;
      } else {
        categoryName.value = 'เอกสารทั่วไป';
      }
    }
  } catch (error) {
    console.error('Error fetching categories:', error);
    categoryName.value = 'เอกสาร';
  } finally {
    loading.value = false;
  }
};

watch(() => route.params.category, () => {
  fetchCategoryName();
});

onMounted(() => {
  fetchCategoryName();
});
</script>

<style scoped>
.container-fluid {
  background-color: #e3f8ff;
  min-height: 100vh;
  padding: 0;
  font-family: Arial, sans-serif;
}
.container {
  padding-top: 20px;
  padding-bottom: 40px;
  max-width: 1200px;
  margin: 0 auto;
}
h1.mfc {
  color: #800080;
  font-weight: 700;
}
</style>
