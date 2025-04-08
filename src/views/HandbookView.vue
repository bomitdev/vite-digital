<template>
  <div class="container mt-5">
    <h3>📂 อัปโหลดคู่มือ PDF</h3>

    <!-- Input เลือกไฟล์ -->
    <div class="mb-3">
      <input type="file" @change="handleFileUpload" class="form-control" />
      <small v-if="selectedFile">📄 ไฟล์ที่เลือก: {{ selectedFile.name }}</small>
    </div>

    <!-- ปุ่มอัปโหลด -->
    <button class="btn btn-primary" @click="uploadFile" :disabled="isUploading">
      <span v-if="isUploading">🚀 กำลังอัปโหลด...</span>
      <span v-else>📤 อัปโหลด</span>
    </button>

    <!-- แสดงความคืบหน้า -->
    <div v-if="uploadProgress > 0" class="progress mt-2">
      <div class="progress-bar" :style="{ width: uploadProgress + '%' }">
        {{ uploadProgress }}%
      </div>
    </div>

    <!-- แสดงข้อความแจ้งเตือน -->
    <div v-if="message" class="alert mt-3" :class="alertClass">
      {{ message }}
    </div>

    <hr />

    <!-- รายการไฟล์ PDF -->
    <h4>📄 รายการคู่มือ</h4>
    <ul v-if="files.length > 0" class="list-group">
      <li v-for="file in files" :key="file.id" class="list-group-item d-flex justify-content-between align-items-center">
        <span>📑 {{ file.filename }}</span>
        <a :href="file.path" target="_blank"  class="btn btn-success btn-sm">🔗 เปิดไฟล์</a> 
        
      </li>
    </ul>
    <p v-else>⏳ กำลังโหลดข้อมูล...</p>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      selectedFile: null,
      uploadProgress: 0,
      message: "",
      alertClass: "",
      isUploading: false,
      files: [],
    };
  },
  methods: {
    // เมื่อเลือกไฟล์
    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
    },

    // อัปโหลดไฟล์
    async uploadFile() {
      if (!this.selectedFile) {
        this.showMessage("⚠️ กรุณาเลือกไฟล์ก่อน", "alert-warning");
        return;
      }

      const fileExtension = this.selectedFile.name.split('.').pop().toLowerCase();
      if (fileExtension !== 'pdf') {
        this.showMessage("⚠️ อนุญาตเฉพาะไฟล์ PDF เท่านั้น", "alert-danger");
        return;
      }

      let formData = new FormData();
      formData.append("file", this.selectedFile);

      this.isUploading = true;
      this.uploadProgress = 0;

      try {
        const response = await axios.post("http://192.168.7.12/vue-app/vite-digital/backend/api-digital/upload.php", formData, {
          headers: { "Content-Type": "multipart/form-data" },
          onUploadProgress: (progressEvent) => {
            this.uploadProgress = Math.round((progressEvent.loaded / progressEvent.total) * 100);
          },
        });

        this.showMessage("✅ " + response.data.message, "alert-success");
        this.fetchFiles(); // โหลดไฟล์ใหม่
      } catch (error) {
        this.showMessage("❌ อัปโหลดล้มเหลว: " + (error.response?.data?.message || error.message), "alert-danger");
      } finally {
        this.isUploading = false;
        this.uploadProgress = 0;
      }
    },

    // ดึงข้อมูลไฟล์จากเซิร์ฟเวอร์
    async fetchFiles() {
      try {
        const response = await axios.get("http://192.168.7.12/vue-app/vite-digital/backend/api-digital/get_files.php");
        this.files = response.data;
      } catch (error) {
        console.error("⛔ เกิดข้อผิดพลาดในการโหลดไฟล์", error);
      }
    },

    // แสดงข้อความแจ้งเตือน
    showMessage(text, type) {
      this.message = text;
      this.alertClass = type;
      setTimeout(() => {
        this.message = "";
      }, 5000);
    },
  },

  mounted() {
    this.fetchFiles();
  },
};
</script>

<style scoped>
.container {
  max-width: 600px;
}
.progress {
  height: 20px;
}
.progress-bar {
  background-color: #28a745;
}
.list-group {
  margin-top: 20px;
}
</style>
