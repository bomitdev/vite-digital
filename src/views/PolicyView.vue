<template>
  <div class="container-fluid">
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <h1 class="mfc fw-bold">นโยบายและระเบียบปฏิบัติ</h1>
        <br />
      </div>
      <hr />
      <div class="container mt-4">
        <ul class="fs-4">
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/policy/doc_p1.pdf" target="_blank"
              >ระเบียบปฎิบัตการรักษาความมั่นคงปลอดภัยของระบบเทคโนโลยีสารสนเทศ</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/policy/doc_p2.pdf" target="_blank"
              >นโยบายแนวทางปฏิบัติในการรักษาความของผู้ป่วยเพื่อคุ้มครองข้อมูลสุขภาพ</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/policy/doc_p3.pdf" target="_blank">
              นโยบายการใช้งานสื่อสังคมออนไลน์ (Social network)</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/policy/doc_p4.pdf" target="_blank">
              นโยบายแนวทางปารปฎิบัติในการรักษาความลับของผู้ป่วยเพื่อคุ้มครองข้อมูลสุขภาพ</a
            >
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "PolicyView",
};
</script>
<style scoped>
.container-fluid {
  background-color: #e3f8ff; /* ตัวอย่าง: สีฟ้าอ่อน */
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  min-height: 100vh; /* ทำให้ body ครอบคลุมความสูงทั้งหมดของ viewport */
}
.container {
  padding-top: 20px; /* เพิ่ม padding ด้านบน */
  padding-bottom: 20px; /* เพิ่ม padding ด้านล่าง */
  margin-left: auto;
  margin-right: auto;
}
h1 {
  color: purple;
  font-family: Arial, sans-serif;
  font-weight: bold;
}
h2 {
  color: orange;
  font-family: Arial, sans-serif;
  font-weight: bold;
}
.policy {
  text-align: center;
}
</style>
