<template>
  <div class="container my-4">
    <h3 class="mb-4">รายการไฟล์ PDF</h3>

    <!-- Upload Form with Drag and Drop -->
    <form @submit.prevent="handleUpload" class="mb-4">
      <div
        class="row g-2 align-items-center"
        @dragover.prevent
        @drop="handleDrop"
        :class="{'drag-over': isDragging}"
      >
        <div class="col-md-6">
          <input
            type="file"
            class="form-control"
            accept="application/pdf"
            @change="handleFileChange"
          />
        </div>
        <div class="col-md-2">
          <button
            type="submit"
            class="btn btn-success w-100"
            :disabled="!selectedFile || uploading"
          >
            {{ uploading ? "กำลังอัปโหลด..." : "อัปโหลด PDF" }}
          </button>
        </div>
      </div>
    </form>

    <div v-if="loading" class="text-center">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <!-- PDF Table -->
    <div v-else>
      <div v-if="files.length > 0">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>ชื่อไฟล์</th>
              <th>วันที่อัปโหลด</th>
              <th>ดูไฟล์</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in files" :key="file.id">
              <td>{{ file.file_name }}</td>
              <td>{{ formatDate(file.uploaded_at) }}</td>
              <td>
                <a
                  :href="getFileUrl(file.file_path)"
                  target="_blank"
                  class="btn btn-sm btn-outline-primary"
                >
                  เปิดไฟล์
                </a>
              </td>
              <td>
                <button
                  class="btn btn-sm btn-danger"
                  @click="confirmDelete(file)"
                >
                  ลบ
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="alert alert-warning">ไม่พบไฟล์ PDF</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "PdfFileList",
  data() {
    return {
      files: [],
      loading: true,
      selectedFile: null,
      uploading: false,
      isDragging: false, // สำหรับตรวจสอบว่าไฟล์กำลังถูกลากเข้ามาหรือไม่
    };
  },
  mounted() {
    this.fetchPdfFiles();
  },
  methods: {
    async fetchPdfFiles() {
      this.loading = true;
      try {
        const response = await fetch(
          "http://192.168.7.12/vue-app/vite-digital/backend/api-digital/get_files_pdf.php"
        );
        if (!response.ok) throw new Error("Network response was not ok");
        const data = await response.json();
        this.files = data;
      } catch (error) {
        console.error("Error fetching PDF files:", error);
      } finally {
        this.loading = false;
      }
    },
    handleFileChange(event) {
      this.selectedFile = event.target.files[0];
    },
    handleDrop(event) {
      event.preventDefault();
      const file = event.dataTransfer.files[0];
      if (file && file.type === "application/pdf") {
        this.selectedFile = file;
      } else {
        alert("โปรดลากไฟล์ PDF เท่านั้น");
      }
      this.isDragging = false;
    },
    handleDragOver(event) {
      event.preventDefault();
      this.isDragging = true;
    },
    handleDragLeave() {
      this.isDragging = false;
    },
    async handleUpload() {
      if (!this.selectedFile) return;

      this.uploading = true;
      const formData = new FormData();
      formData.append("pdf_file", this.selectedFile);

      try {
        const response = await fetch(
          "http://192.168.7.12/vue-app/vite-digital/backend/api-digital/upload_pdf.php",
          {
            method: "POST",
            body: formData,
          }
        );

        const result = await response.json();

        if (result.success) {
          alert("อัปโหลดสำเร็จ!");
          this.selectedFile = null;
          this.fetchPdfFiles(); // refresh list
        } else {
          alert(result.error || "เกิดข้อผิดพลาดในการอัปโหลด");
        }
      } catch (error) {
        console.error("Upload error:", error);
        alert("เกิดข้อผิดพลาดในการเชื่อมต่อ");
      } finally {
        this.uploading = false;
      }
    },
    getFileUrl(filePath) {
      return `http://192.168.7.12/vue-app/vite-digital/backend/api-digital/uploads/${filePath}`;
    },
    formatDate(dateString) {
      const options = {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      };
      return new Date(dateString).toLocaleDateString("th-TH", options);
    },
    async confirmDelete(file) {
      if (!confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์ "${file.file_name}" ?`))
        return;

      try {
        const response = await fetch(
          "http://192.168.7.12/vue-app/vite-digital/backend/api-digital/delete_pdf.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: file.id, file_path: file.file_path }),
          }
        );

        const result = await response.json();

        if (result.success) {
          alert("ลบไฟล์สำเร็จ");
          this.fetchPdfFiles(); // รีเฟรชรายการ
        } else {
          alert(result.error || "ลบไฟล์ไม่สำเร็จ");
        }
      } catch (error) {
        console.error("Delete error:", error);
        alert("เกิดข้อผิดพลาดในการลบไฟล์");
      }
    },
  },
};
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
}

.drag-over {
  border: 2px dashed #007bff;
  background-color: #f1f1f1;
}
</style>
