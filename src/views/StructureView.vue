<template>
  <div class="p-3 mb-2 bg-info-subtle text-dark">
    <div class="container-fluid">
      <br />

      <img
        src="/src/components/img/structure.png"
        class="d-block w-100"
        alt="..."
      />
    </div>
  </div>
</template>

<script>
export default {
  name: "StructureView",
};
</script>

<style scoped>
.container-fluid {
  background-color: #e3f8ff; /* ตัวอย่าง: สีฟ้าอ่อน */
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  display: flex; /* ใช้ flexbox */
  justify-content: center; /* จัดกึ่งกลางแนวนอน */
  align-items: center; /* จัดกึ่งกลางแนวตั้ง */
  height: auto; /* ความสูงเต็มหน้าจอ */
}

img {
  max-width: 75%;
  max-height: 100%; /* จำกัดความสูงของภาพ */
  object-fit: cover; /* ครอบคลุมพื้นที่ของ Carousel โดยไม่ผิดเพี้ยน */
}
h1 {
  color: blue;
  font-weight: bold;
  font-family: Arial, sans-serif;
}
</style>
