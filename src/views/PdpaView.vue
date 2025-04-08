<template>
  <div class="container-fluid">
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <h1 class="mfc fw-bold">นโยบายความคุ้มครองส่วนบุคคล</h1>
        <br />
      </div>
      <hr />
      <div class="text-align: center;">
        <div class="container mt-4">
          <h2>(Personal Data Protection Act : PDPA)</h2>
          <br />
          <img
            src="/src/components/img/pdpa.png"
            class="d-block w-100"
            alt="..."
          />
        </div>
      </div>
      <hr />
      <div class="container mt-4">
        <ul class="fs-4">
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/doc1.pdf" target="_blank"
              >Privacy Policy ปฏิบัติตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคลฯ ศทส.
              ที่ สธ.0212ว 410 ลงวันที่ 25 พฤษภาคม 2565</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/doc1.pdf" target="_blank"
              >Privacy Notice ปฏิบัติตามประกาศ ศทส. แนวปฏิบัติการคุ้มครองฯ ที่
              สธ. 0212ว 11424 ลงวันที่ 25 พฤษภาคม 2565</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/doc1.pdf" target="_blank"
              >Privacy Notice ปฏิบัติตามประกาศ ศทส. แนวปฏิบัติการคุ้มครองฯ ที่
              สธ. 0212ว 14039 ลงวันที่ 24 มิถุนายน 2565 ฉบับ 2</a
            >
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "PdpaView",
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
</style>
